<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00025Rule implements MscRuleInterface
{
    private const CODE = 'D1_00025';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas do Passivo (classe 2, exceto PL 2.3) devem apresentar saldo credor.
     * Valor negativo indica saldo invertido.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if (! $this->isContaPassivo($lineData->conta)) {
            return null;
        }

        if ($lineData->valor < 0.0) {
            return sprintf(
                'Conta do Passivo %s (linha %d) possui saldo negativo (%.2f), indicando saldo invertido.',
                $lineData->conta,
                $lineData->linha,
                $lineData->valor,
            );
        }

        return null;
    }

    private function isContaPassivo(string $conta): bool
    {
        $conta = trim($conta);

        if (str_starts_with($conta, '2.3')) {
            return false;
        }

        return str_starts_with($conta, '2');
    }
}
