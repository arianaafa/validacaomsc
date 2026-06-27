<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00017Rule implements MscRuleInterface
{
    private const CODE = 'D1_00017';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Valores negativos na MSC indicam inconsistência estrutural do saldo reportado.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($lineData->valor < 0.0) {
            return sprintf(
                'A linha %d possui valor negativo (%.2f), o que não é permitido na MSC.',
                $lineData->linha,
                $lineData->valor,
            );
        }

        return null;
    }
}
