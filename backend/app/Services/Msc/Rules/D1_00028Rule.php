<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00028Rule implements MscRuleInterface
{
    private const CODE = 'D1_00028';

    private const IC_ATRIBUTO_FINANCEIRO = 'F';

    private const IC_FONTE_RECURSOS = 'FR';

    /** Valor do TIPO que indica atributo financeiro ativo. */
    private const TIPO_ATIVO = '1';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas com atributo financeiro (F) exigem detalhamento da Fonte de Recursos (FR).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if (! $this->isAtributoFinanceiroAtivo($lineData->ics)) {
            return null;
        }

        $fonteRecursos = $this->resolveTipoPorIc($lineData->ics, self::IC_FONTE_RECURSOS);

        if ($fonteRecursos !== '') {
            return null;
        }

        return sprintf(
            'Conta %s (linha %d) possui atributo financeiro (F) ativo sem detalhamento de Fonte de Recursos (FR).',
            $lineData->conta,
            $lineData->linha,
        );
    }

    /**
     * @param array<string, string> $ics
     */
    private function isAtributoFinanceiroAtivo(array $ics): bool
    {
        for ($indice = 1; $indice <= 6; $indice++) {
            $ic = strtoupper(trim($ics["IC{$indice}"] ?? ''));
            $tipo = trim($ics["TIPO{$indice}"] ?? '');

            if ($ic === self::IC_ATRIBUTO_FINANCEIRO && $tipo === self::TIPO_ATIVO) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, string> $ics
     */
    private function resolveTipoPorIc(array $ics, string $codigoIc): string
    {
        $codigoIc = strtoupper($codigoIc);

        for ($indice = 1; $indice <= 6; $indice++) {
            $ic = strtoupper(trim($ics["IC{$indice}"] ?? ''));

            if ($ic === $codigoIc) {
                return trim($ics["TIPO{$indice}"] ?? '');
            }
        }

        return '';
    }
}
