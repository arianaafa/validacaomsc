<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00021Rule implements MscRuleInterface
{
    private const CODE = 'D1_00021';

    /** Natureza devedora esperada para contas do Ativo (MCASP). */
    private const NATUREZA_DEVEDORA = 'D';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas do Ativo (classe 1) devem apresentar saldo devedor.
     * Saldo negativo ou natureza credora indicam inversão incorreta.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if (! $this->isContaAtivo($lineData->conta)) {
            return null;
        }

        $natureza = strtoupper(trim($lineData->naturezaValor));

        if ($lineData->valor < 0.0) {
            return sprintf(
                'Conta do Ativo %s (linha %d) possui saldo negativo (%.2f), indicando saldo invertido.',
                $lineData->conta,
                $lineData->linha,
                $lineData->valor,
            );
        }

        if ($natureza !== self::NATUREZA_DEVEDORA) {
            return sprintf(
                'Conta do Ativo %s (linha %d) possui natureza "%s" inconsistente; esperada natureza devedora (D).',
                $lineData->conta,
                $lineData->linha,
                $lineData->naturezaValor,
            );
        }

        return null;
    }

    private function isContaAtivo(string $conta): bool
    {
        return str_starts_with(trim($conta), '1');
    }
}
