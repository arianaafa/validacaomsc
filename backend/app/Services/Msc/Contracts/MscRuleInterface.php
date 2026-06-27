<?php

declare(strict_types=1);

namespace App\Services\Msc\Contracts;

interface MscRuleInterface
{
    public function getCode(): string;

    public function validate(MscLineData $lineData): ?string;
}
