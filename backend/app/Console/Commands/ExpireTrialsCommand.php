<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Lead\LeadProvisioningService;
use Illuminate\Console\Command;

final class ExpireTrialsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'aura:expire-trials';

    /**
     * @var string
     */
    protected $description = 'Expira trials vencidos e desativa os usuários correspondentes';

    public function handle(LeadProvisioningService $leadProvisioningService): int
    {
        $expiredCount = $leadProvisioningService->expireExpiredTrials();

        $this->info("Trials expirados processados: {$expiredCount}");

        return self::SUCCESS;
    }
}
