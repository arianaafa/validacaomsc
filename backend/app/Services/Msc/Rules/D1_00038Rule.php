<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00038Rule implements MscRuleInterface
{
    private const CODE = 'D1_00038';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const NATUREZA_DEVEDORA = 'D';

    private const NATUREZA_CREDORA = 'C';

    private const MENSAGEM_INCONSISTENCIA = 'contas de previsão e execução orçamentária com saldo invertido incorretamente.';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas das classes 5 e 6 devem apresentar saldo final (ending_balance) não zerado
     * com natureza contábil conforme o PCASP Estendido (grupos 5.1/6.2/6.3 devedores;
     * grupos 5.2/5.3/6.1 credores).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_FINAL) {
            return null;
        }

        if ($lineData->valor == 0.0) {
            return null;
        }

        $naturezaEsperada = $this->resolveNaturezaEsperada($lineData->conta);

        if ($naturezaEsperada === null) {
            return null;
        }

        if ($this->naturezaInformadaCorresponde($lineData->naturezaValor, $naturezaEsperada)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function resolveNaturezaEsperada(string $conta): ?string
    {
        $contaNormalizada = $this->normalizeConta($conta);

        if (strlen($contaNormalizada) < 2) {
            return null;
        }

        $classe = $contaNormalizada[0];
        $grupo = $contaNormalizada[1];

        if ($classe === '5') {
            return match ($grupo) {
                '1' => self::NATUREZA_DEVEDORA,
                '2', '3' => self::NATUREZA_CREDORA,
                default => null,
            };
        }

        if ($classe === '6') {
            return match ($grupo) {
                '1' => self::NATUREZA_CREDORA,
                '2', '3' => self::NATUREZA_DEVEDORA,
                default => null,
            };
        }

        return null;
    }

    private function naturezaInformadaCorresponde(string $naturezaValor, string $naturezaEsperada): bool
    {
        $natureza = strtoupper(trim($naturezaValor));

        if ($naturezaEsperada === self::NATUREZA_DEVEDORA) {
            return $natureza === self::NATUREZA_DEVEDORA || $natureza === 'DEVEDORA';
        }

        return $natureza === self::NATUREZA_CREDORA || $natureza === 'CREDORA';
    }

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }

    private function normalizeTipoValor(string $tipoValor): string
    {
        return strtolower(trim($tipoValor));
    }
}
