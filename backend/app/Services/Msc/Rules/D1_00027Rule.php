<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00027Rule implements MscRuleInterface
{
    private const CODE = 'D1_00027';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas de Patrimônio Líquido (2.3) devem manter saldo credor.
     * Saldo final negativo indica inversão ou inconsistência contábil.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if (! str_starts_with(trim($lineData->conta), '2.3')) {
            return null;
        }

        if ($lineData->valor < 0.0) {
            return sprintf(
                'Conta de Patrimônio Líquido %s (linha %d) possui saldo final negativo (%.2f).',
                $lineData->conta,
                $lineData->linha,
                $lineData->valor,
            );
        }

        return null;
    }
}
