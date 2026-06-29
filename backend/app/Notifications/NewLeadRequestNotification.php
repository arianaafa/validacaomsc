<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\LeadRequestRole;
use App\Models\LeadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class NewLeadRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly LeadRequest $leadRequest,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleLabel = $this->resolveRoleLabel($this->leadRequest->role);

        $mail = (new MailMessage())
            ->subject('Novo lead: solicitação de demonstração Audita MSC')
            ->greeting('Nova solicitação de demonstração')
            ->line('Um município/órgão demonstrou interesse em uma instância dedicada do Audita MSC.')
            ->line('**Nome:** '.$this->leadRequest->name)
            ->line('**E-mail:** '.$this->leadRequest->email)
            ->line('**Telefone:** '.$this->leadRequest->phone)
            ->line('**Prefeitura/Órgão:** '.$this->leadRequest->organization_name)
            ->line('**Cargo:** '.$roleLabel);

        if ($this->leadRequest->message !== null && $this->leadRequest->message !== '') {
            $mail->line('**Mensagem:** '.$this->leadRequest->message);
        }

        return $mail->line('Entre em contato em até 24 horas para apresentar a instância dedicada.');
    }

    private function resolveRoleLabel(LeadRequestRole $role): string
    {
        return match ($role) {
            LeadRequestRole::Secretario => 'Secretário(a) de Finanças',
            LeadRequestRole::Contador => 'Contador(a)',
            LeadRequestRole::Auditor => 'Auditor(a)',
            LeadRequestRole::Outros => 'Outros',
        };
    }
}
