<?php

declare(strict_types=1);

namespace App\Services\Msc\Contracts;

interface MscRuleResultInterface extends MscRuleInterface
{
    public function validateResult(MscLineData $lineData): ?MscRuleValidationResult;
}
