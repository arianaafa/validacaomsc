<?php

declare(strict_types=1);

namespace Tests\Feature\Lead;

use App\Enums\LeadRequestStatus;
use App\Models\LeadRequest;
use App\Models\Municipality;
use App\Models\User;
use App\Notifications\LeadTrialAccessNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

final class LeadTrialFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_can_start_trial_from_pending_lead(): void
    {
        Notification::fake();

        $admin = User::factory()->superAdmin()->create();
        $lead = LeadRequest::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->postJson("/api/admin/lead-requests/{$lead->id}/start-trial");

        $response
            ->assertCreated()
            ->assertJsonPath('lead_request.status', 'trial')
            ->assertJsonPath('email_sent', true)
            ->assertJsonStructure(['temporary_password', 'user' => ['email']]);

        Notification::assertSentOnDemand(
            LeadTrialAccessNotification::class,
            fn ($notification, $channels, $notifiable) => $notifiable->routes['mail'] === $lead->email,
        );

        $this->assertDatabaseHas('municipalities', [
            'ibge_code' => $lead->ibge_code,
        ]);

        $this->assertDatabaseHas('users', [
            'email' => $lead->email,
            'is_trial' => true,
            'is_active' => true,
        ]);
    }

    public function test_trial_user_can_upload_multiple_spreadsheets(): void
    {
        $lead = LeadRequest::factory()->create();
        $admin = User::factory()->superAdmin()->create();

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/lead-requests/{$lead->id}/start-trial")->assertCreated();

        $trialUser = User::query()->where('email', $lead->email)->firstOrFail();
        Sanctum::actingAs($trialUser);

        $csv = UploadedFile::fake()->createWithContent(
            'msc.csv',
            "2507507EX\nCONTA;IC1;TIPO1;IC2;TIPO2;IC3;TIPO3;IC4;TIPO4;IC5;TIPO5;IC6;TIPO6;Valor;Tipo_valor;Natureza_valor\n",
        );

        $this->postJson('/api/msc/uploads', [
            'file' => $csv,
            'periodo' => '2026-01',
            'tipo_msc' => 'agregada',
        ])->assertCreated();

        $secondCsv = UploadedFile::fake()->createWithContent(
            'msc-2.csv',
            "2507507EX\nCONTA;IC1;TIPO1;IC2;TIPO2;IC3;TIPO3;IC4;TIPO4;IC5;TIPO5;IC6;TIPO6;Valor;Tipo_valor;Natureza_valor\n",
        );

        $this->postJson('/api/msc/uploads', [
            'file' => $secondCsv,
            'periodo' => '2026-02',
            'tipo_msc' => 'agregada',
        ])->assertCreated();
    }

    public function test_expired_trial_user_is_deactivated(): void
    {
        $municipality = Municipality::query()->create([
            'name' => 'João Pessoa',
            'ibge_code' => '2507507',
        ]);

        $user = User::factory()->trial()->create([
            'municipality_id' => $municipality->id,
            'trial_expires_at' => now()->subMinute(),
        ]);

        LeadRequest::factory()->create([
            'email' => $user->email,
            'status' => LeadRequestStatus::Trial,
            'user_id' => $user->id,
            'trial_started_at' => now()->subDay(),
            'trial_expires_at' => now()->subMinute(),
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')->assertForbidden();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('lead_requests', [
            'user_id' => $user->id,
            'status' => LeadRequestStatus::Failed->value,
        ]);
    }

    public function test_superadmin_can_approve_trial_lead(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $lead = LeadRequest::factory()->create();

        Sanctum::actingAs($admin);
        $this->postJson("/api/admin/lead-requests/{$lead->id}/start-trial")->assertCreated();

        $response = $this->postJson("/api/admin/lead-requests/{$lead->id}/approve");

        $response
            ->assertOk()
            ->assertJsonPath('lead_request.status', 'approved');

        $user = User::query()->where('email', $lead->email)->firstOrFail();

        $this->assertFalse($user->isTrial());
        $this->assertTrue($user->isActive());
        $this->assertNull($user->trial_expires_at);
    }
}
