<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;

final class D1_00035Rule implements MscStatefulRuleInterface
{
    private const CODE = 'D1_00035';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const NATUREZA_CREDORA = 'C';

    private const PREFIXO_CLASSE_VPA = '4';

    private const MES_ENCERRAMENTO = 13;

    private const MENSAGEM_INCONSISTENCIA = 'contas de VPA com saldo invertido incorretamente.';

    private int $mes = 0;

    public function getCode(): string
    {
        return self::CODE;
    }

    public function prepare(string $idEnte, int $ano, int $mes, string $tipoMatriz): void
    {
        $this->reset();
        $this->mes = $mes;
    }

    public function reset(): void
    {
        $this->mes = 0;
    }

    /**
     * Contas da classe VPA (4) devem apresentar saldo final (ending_balance) credor.
     * Não se aplica à MSC de encerramento (mês 13).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($this->mes === self::MES_ENCERRAMENTO) {
            return null;
        }

        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_FINAL) {
            return null;
        }

        if (! $this->isContaClasseVpa($lineData->conta)) {
            return null;
        }

        if ($this->isNaturezaCredora($lineData->naturezaValor)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaClasseVpa(string $conta): bool
    {
        $contaNormalizada = $this->normalizeConta($conta);

        return str_starts_with($contaNormalizada, self::PREFIXO_CLASSE_VPA);
    }

    private function isNaturezaCredora(string $naturezaValor): bool
    {
        $natureza = strtoupper(trim($naturezaValor));

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
