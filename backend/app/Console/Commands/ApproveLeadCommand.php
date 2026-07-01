<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\LeadRequest;
use App\Services\Lead\LeadProvisioningService;
use Illuminate\Console\Command;

final class ApproveLeadCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'aura:lead-approve {leadRequestId : UUID do lead}';

    /**
     * @var string
     */
    protected $description = 'Aprova um lead em trial e torna o usuário definitivo';

    public function handle(LeadProvisioningService $leadProvisioningService): int
    {
        $lead = LeadRequest::query()->find($this->argument('leadRequestId'));

        if ($lead === null) {
            $this->error('Lead não encontrado.');

            return self::FAILURE;
        }

        try {
            $result = $leadProvisioningService->approve($lead);
        } catch (\Illuminate\Validation\ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->info($result['message']);

        return self::SUCCESS;
    }
}
