<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00021Rule implements MscRuleInterface
{
    private const CODE = 'D1_00021';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const NATUREZA_DEVEDORA = 'D';

    private const MENSAGEM_INCONSISTENCIA = 'contas do ativo com saldo invertido incorretamente.';

    /**
     * Grupos do Ativo (PCASP Estendido) com natureza devedora padrão.
     *
     * @var list<string>
     */
    private const PREFIXOS_CONTA_ATIVO = [
        '1111',
        '1121',
        '1125',
        '1231',
        '1232',
    ];

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas dos grupos do Ativo devem apresentar saldo final (ending_balance) devedor.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_FINAL) {
            return null;
        }

        if (! $this->isContaGrupoAtivo($lineData->conta)) {
            return null;
        }

        if ($this->isNaturezaDevedora($lineData->naturezaValor)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaGrupoAtivo(string $conta): bool
    {
        $contaNormalizada = $this->normalizeConta($conta);

        foreach (self::PREFIXOS_CONTA_ATIVO as $prefixo) {
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
