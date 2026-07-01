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

        return self::SUCCESS;
    }
}
