<?php

declare(strict_types=1);

namespace App\Services\Lead;

use App\Enums\LeadRequestStatus;
use App\Helpers\IbgeHelper;
use App\Models\LeadRequest;
use App\Models\Municipality;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class LeadProvisioningService
{
    public function listLeads(): array
    {
        return LeadRequest::query()
            ->with('user:id,name,email,is_active,is_trial,trial_expires_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (LeadRequest $lead): array => $this->formatLeadRequest($lead))
            ->all();
    }

    /**
     * @return array{
     *     message: string,
     *     lead_request: array<string, mixed>,
     *     user: array{id: int, name: string, email: string},
     *     temporary_password: string
     * }
     */
    public function startTrial(LeadRequest $lead): array
    {
        if ($lead->status !== LeadRequestStatus::Pending) {
            throw ValidationException::withMessages([
                'lead' => ['Somente leads pendentes podem receber acesso trial.'],
            ]);
        }

        if (User::query()->where('email', $lead->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Já existe um usuário cadastrado com o e-mail deste lead.'],
            ]);
        }

        $temporaryPassword = Str::password(16);
        $trialStartedAt = now();
        $trialExpiresAt = $trialStartedAt->copy()->addDays($this->trialDays());

        $user = DB::transaction(function () use ($lead, $temporaryPassword, $trialStartedAt, $trialExpiresAt): User {
            $municipality = $this->resolveMunicipality($lead);

            $user = User::query()->create([
                'name' => $lead->name,
                'email' => $lead->email,
                'password' => $temporaryPassword,
                'municipality_id' => $municipality->id,
                'is_superadmin' => false,
                'force_password_change' => true,
                'is_active' => true,
                'is_trial' => true,
                'trial_expires_at' => $trialExpiresAt,
            ]);

            $lead->update([
                'status' => LeadRequestStatus::Trial,
                'user_id' => $user->id,
                'trial_started_at' => $trialStartedAt,
                'trial_expires_at' => $trialExpiresAt,
            ]);

            return $user;
        });

        $lead->refresh();
        $lead->load('user');

        return [
            'message' => 'Acesso trial provisionado com sucesso.',
            'lead_request' => $this->formatLeadRequest($lead),
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'temporary_password' => $temporaryPassword,
        ];
    }

    /**
     * @return array{message: string, lead_request: array<string, mixed>}
     */
    public function approve(LeadRequest $lead): array
    {
        if ($lead->status !== LeadRequestStatus::Trial) {
            throw ValidationException::withMessages([
                'lead' => ['Somente leads em trial podem ser aprovados.'],
            ]);
        }

        $user = $lead->user;

        if ($user === null) {
            throw ValidationException::withMessages([
                'lead' => ['Lead trial sem usuário vinculado.'],
            ]);
        }

        DB::transaction(function () use ($lead, $user): void {
            $user->update([
                'is_trial' => false,
                'trial_expires_at' => null,
                'is_active' => true,
                'force_password_change' => false,
            ]);

            $lead->update([
                'status' => LeadRequestStatus::Approved,
                'approved_at' => now(),
            ]);
        });

        $lead->refresh();
        $lead->load('user');

        return [
            'message' => 'Lead aprovado. O usuário agora possui acesso definitivo.',
            'lead_request' => $this->formatLeadRequest($lead),
        ];
    }

    /**
     * @return array{message: string, lead_request: array<string, mixed>}
     */
    public function fail(LeadRequest $lead): array
    {
        if (! in_array($lead->status, [LeadRequestStatus::Pending, LeadRequestStatus::Trial], true)) {
            throw ValidationException::withMessages([
                'lead' => ['Este lead não pode ser marcado como falho ou expirado.'],
            ]);
        }

        DB::transaction(function () use ($lead): void {
            $user = $lead->user;

            if ($user !== null) {
                $user->update([
                    'is_active' => false,
                    'is_trial' => false,
                ]);
                $user->tokens()->delete();
            }

            $lead->update([
                'status' => LeadRequestStatus::Failed,
            ]);
        });

        $lead->refresh();
        $lead->load('user');

        return [
            'message' => 'Lead marcado como falho/expirado.',
            'lead_request' => $this->formatLeadRequest($lead),
        ];
    }

    public function expireTrialIfNeeded(User $user): void
    {
        if (! $user->isTrial() || $user->trial_expires_at === null) {
            return;
        }

        if ($user->trial_expires_at->isFuture()) {
            return;
        }

        $lead = LeadRequest::query()
            ->where('user_id', $user->id)
            ->where('status', LeadRequestStatus::Trial)
            ->first();

        if ($lead !== null) {
            $this->fail($lead);

            return;
        }

        $user->update([
            'is_active' => false,
            'is_trial' => false,
        ]);
        $user->tokens()->delete();
    }

    public function assertUserCanUpload(User $user): void
    {
        $this->expireTrialIfNeeded($user);
        $user->refresh();

        if (! $user->isActive()) {
            throw ValidationException::withMessages([
                'file' => ['Seu período de teste expirou. Entre em contato com a equipe comercial.'],
            ]);
        }
    }

    public function expireExpiredTrials(): int
    {
        $expiredLeads = LeadRequest::query()
            ->where('status', LeadRequestStatus::Trial)
            ->whereNotNull('trial_expires_at')
            ->where('trial_expires_at', '<=', now())
            ->get();

        foreach ($expiredLeads as $lead) {
            $this->fail($lead);
        }

        return $expiredLeads->count();
    }

    /**
     * @return array<string, mixed>
     */
    public function formatLeadRequest(LeadRequest $lead): array
    {
        return [
            'id' => $lead->id,
            'name' => $lead->name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'organization_name' => $lead->organization_name,
            'cnpj' => $lead->cnpj,
            'ibge_code' => $lead->ibge_code,
            'role' => $lead->role->value,
            'message' => $lead->message,
            'status' => $lead->status->value,
            'user_id' => $lead->user_id,
            'trial_started_at' => $lead->trial_started_at?->toIso8601String(),
            'trial_expires_at' => $lead->trial_expires_at?->toIso8601String(),
            'approved_at' => $lead->approved_at?->toIso8601String(),
            'created_at' => $lead->created_at?->toIso8601String(),
            'user' => $lead->user !== null ? [
                'id' => $lead->user->id,
                'name' => $lead->user->name,
                'email' => $lead->user->email,
                'is_active' => $lead->user->isActive(),
                'is_trial' => $lead->user->isTrial(),
                'trial_expires_at' => $lead->user->trial_expires_at?->toIso8601String(),
            ] : null,
        ];
    }

    private function resolveMunicipality(LeadRequest $lead): Municipality
    {
        $ibgeCode = trim($lead->ibge_code);
        $name = trim($lead->organization_name);

        if ($name === '') {
            $ente = IbgeHelper::getMunicipioByCode($ibgeCode);
            $name = $ente['municipio'] !== '' ? $ente['municipio'] : sprintf('Município IBGE %s', $ibgeCode);
        }

        return Municipality::query()->firstOrCreate(
            ['ibge_code' => $ibgeCode],
            ['name' => $name],
        );
    }

    private function trialDays(): int
    {
        return max(1, (int) config('leads.trial_days', 7));
    }
}
