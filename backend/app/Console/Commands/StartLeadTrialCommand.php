<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LeadRequest;
use App\Services\Lead\LeadProvisioningService;
use Illuminate\Console\Command;

final class StartLeadTrialCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'aura:lead-start-trial {leadRequestId : UUID do lead}';

    /**
     * @var string
     */
    protected $description = 'Provisiona acesso trial a partir de um lead pendente';

    public function handle(LeadProvisioningService $leadProvisioningService): int
    {
        $lead = LeadRequest::query()->find($this->argument('leadRequestId'));

        if ($lead === null) {
            $this->error('Lead não encontrado.');

            return self::FAILURE;
        }

        try {
            $result = $leadProvisioningService->startTrial($lead);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->info($result['message']);
        $this->line("Usuário: {$result['user']['email']}");
        $this->line("Senha temporária: {$result['temporary_password']}");

        if ($result['email_sent']) {
            $this->line("E-mail de acesso enviado para: {$result['user']['email']}");
        } else {
            $this->warn('Não foi possível enviar o e-mail de acesso. Verifique MAIL_* no .env e a conectividade SMTP.');
            $this->warn('Com MAIL_MAILER=log, o conteúdo do e-mail fica em storage/logs/laravel.log.');
        }

        return self::SUCCESS;
    }
}
