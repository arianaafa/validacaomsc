<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;

final class D1_00034Rule implements MscStatefulRuleInterface
{
    private const CODE = 'D1_00034';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const NATUREZA_DEVEDORA = 'D';

    private const MES_ENCERRAMENTO = 13;

    private const MENSAGEM_INCONSISTENCIA = 'contas de VPD com saldo invertido incorretamente.';

    /**
     * Grupos de VPD (PCASP Estendido) com natureza devedora padrão.
     *
     * @var list<string>
     */
    private const PREFIXOS_CONTA_VPD = [
        '311',
        '312',
        '313',
        '321',
        '322',
        '323',
        '331',
        '332',
        '333',
        '351',
        '352',
        '353',
        '361',
        '362',
        '363',
    ];

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
     * Contas dos grupos de VPD devem apresentar saldo final (ending_balance) devedor.
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

        if (! $this->isContaGrupoVpd($lineData->conta)) {
            return null;
        }

        if ($this->isNaturezaDevedora($lineData->naturezaValor)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaGrupoVpd(string $conta): bool
    {
        $contaNormalizada = $this->normalizeConta($conta);

        foreach (self::PREFIXOS_CONTA_VPD as $prefixo) {
            if (str_starts_with($contaNormalizada, $prefixo)) {
                return true;
            }
        }

        return false;
    }

    private function isNaturezaDevedora(string $naturezaValor): bool
    {
        $natureza = strtoupper(trim($naturezaValor));

        return $natureza === self::NATUREZA_DEVEDORA || $natureza === 'DEVEDORA';
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
