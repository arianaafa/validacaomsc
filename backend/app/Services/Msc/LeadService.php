<?php

declare(strict_types=1);

namespace App\Services\Msc;

use App\Enums\LeadRequestRole;
use App\Enums\LeadRequestStatus;
use App\Models\LeadRequest;
use App\Notifications\NewLeadRequestNotification;
use Illuminate\Support\Facades\Notification;

final class LeadService
{
    /**
     * @param array{
     *     name: string,
     *     email: string,
     *     phone: string,
     *     organization_name: string,
     *     cnpj: string,
     *     ibge_code: string,
     *     role: string,
     *     message: string|null
     * } $data
     */
    public function createLead(array $data): LeadRequest
    {
        $leadRequest = LeadRequest::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'organization_name' => $data['organization_name'],
            'cnpj' => $data['cnpj'],
            'ibge_code' => $data['ibge_code'],
            'role' => LeadRequestRole::from($data['role']),
            'message' => $data['message'],
            'status' => LeadRequestStatus::Pending,
        ]);

        $this->notifyAdministrator($leadRequest);

        return $leadRequest;
    }

    /**
     * @return array{
     *     id: string,
     *     name: string,
     *     email: string,
     *     phone: string,
     *     organization_name: string,
     *     cnpj: string,
     *     ibge_code: string,
     *     role: string,
     *     message: string|null,
     *     status: string,
     *     created_at: string|null
     * }
     */
    public function formatLeadRequest(LeadRequest $leadRequest): array
    {
        return [
            'id' => $leadRequest->id,
            'name' => $leadRequest->name,
            'email' => $leadRequest->email,
            'phone' => $leadRequest->phone,
            'organization_name' => $leadRequest->organization_name,
            'cnpj' => $leadRequest->cnpj,
            'ibge_code' => $leadRequest->ibge_code,
            'role' => $leadRequest->role->value,
            'message' => $leadRequest->message,
            'status' => $leadRequest->status->value,
            'user_id' => $leadRequest->user_id,
            'trial_started_at' => $leadRequest->trial_started_at?->toIso8601String(),
            'trial_expires_at' => $leadRequest->trial_expires_at?->toIso8601String(),
            'approved_at' => $leadRequest->approved_at?->toIso8601String(),
            'created_at' => $leadRequest->created_at?->toIso8601String(),
        ];
    }

    private function notifyAdministrator(LeadRequest $leadRequest): void
    {
        $adminEmail = config('leads.admin_email');

        if (! is_string($adminEmail) || $adminEmail === '') {
            return;
        }

        Notification::route('mail', $adminEmail)
            ->notify(new NewLeadRequestNotification($leadRequest));
    }
}
