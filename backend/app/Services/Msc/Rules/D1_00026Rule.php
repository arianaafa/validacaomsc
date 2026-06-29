<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00026Rule implements MscRuleInterface
{
    private const CODE = 'D1_00026';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const NATUREZA_CREDORA = 'C';

    private const MENSAGEM_INCONSISTENCIA = 'contas de patrimônio líquido com saldo invertido incorretamente.';

    /**
     * Grupos do Patrimônio Líquido (PCASP Estendido) com natureza credora padrão.
     *
     * @var list<string>
     */
    private const PREFIXOS_CONTA_PATRIMONIO_LIQUIDO = [
        '2311',
        '2312',
        '232',
        '233',
        '234',
        '235',
        '236',
    ];

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas dos grupos do Patrimônio Líquido devem apresentar saldo final (ending_balance) credor.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_FINAL) {
            return null;
        }

        if (! $this->isContaGrupoPatrimonioLiquido($lineData->conta)) {
            return null;
        }

        if ($this->isNaturezaCredora($lineData->naturezaValor)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaGrupoPatrimonioLiquido(string $conta): bool
    {
        $contaNormalizada = $this->normalizeConta($conta);

        foreach (self::PREFIXOS_CONTA_PATRIMONIO_LIQUIDO as $prefixo) {
            if (str_starts_with($contaNormalizada, $prefixo)) {
                return true;
            }
        }

        return false;
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
