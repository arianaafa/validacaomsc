<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\LeadRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

final class LeadTrialAccessNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly LeadRequest $leadRequest,
        private readonly string $temporaryPassword,
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
        $loginUrl = rtrim((string) config('leads.frontend_url'), '/').'/login';
        $trialDays = max(1, (int) config('leads.trial_days', 7));
        $expiresAt = $this->leadRequest->trial_expires_at instanceof Carbon
            ? $this->leadRequest->trial_expires_at->timezone(config('app.timezone'))->format('d/m/Y H:i')
            : null;

        $mail = (new MailMessage())
            ->subject('Seu acesso de teste ao Audita MSC')
            ->greeting('Olá, '.$this->leadRequest->name.'!')
            ->line('Seu teste gratuito de **'.$trialDays.' dias** no Audita MSC foi liberado para **'.$this->leadRequest->organization_name.'**.')
            ->line('Use os dados abaixo para entrar na plataforma:')
            ->line('**E-mail de acesso:** '.$this->leadRequest->email)
            ->line('**Senha temporária:** '.$this->temporaryPassword)
            ->action('Acessar o Audita MSC', $loginUrl)
            ->line('Por segurança, você precisará **criar uma nova senha** no primeiro acesso.')
            ->line('Durante o período de teste, você pode importar planilhas MSC sem limite.');

        if ($expiresAt !== null) {
            $mail->line('Seu acesso expira em: **'.$expiresAt.'**.');
        }

        return $mail->salutation('Atenciosamente, Equipe Audita MSC · Aura Tech');
    }
}
