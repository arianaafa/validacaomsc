<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00027Rule implements MscRuleInterface
{
    private const CODE = 'D1_00027';

    private const IC_ATRIBUTO_SUPERAVIT_FINANCEIRO = 'FP';

    private const IC_FONTE_RECURSOS = 'FR';

    private const VALOR_ATRIBUTO_FINANCEIRO = '1';

    private const MENSAGEM_INCONSISTENCIA = 'contas com atributo F (financeiro) sem detalhamento de fonte ou destinação de recursos.';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas com atributo financeiro (FP = 1) exigem detalhamento de Fonte de Recursos (FR).
     */
    public function validate(MscLineData $lineData): ?string
    {
        $icMap = $this->extractIcMap($lineData->ics);

        if (($icMap[self::IC_ATRIBUTO_SUPERAVIT_FINANCEIRO] ?? '') !== self::VALOR_ATRIBUTO_FINANCEIRO) {
            return null;
        }

        $fonteRecursos = trim($icMap[self::IC_FONTE_RECURSOS] ?? '');

        if ($fonteRecursos !== '') {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    /**
     * @param array<string, string> $ics
     * @return array<string, string>
     */
    private function extractIcMap(array $ics): array
    {
        $icMap = [];

        for ($indice = 1; $indice <= 6; $indice++) {
            $valorIc = trim($ics["IC{$indice}"] ?? '');
            $tipoIc = strtoupper(trim($ics["TIPO{$indice}"] ?? ''));

            if ($valorIc === '' || $tipoIc === '') {
                continue;
            }

            $icMap[$tipoIc] = $valorIc;
        }

        return $icMap;
    }
}
