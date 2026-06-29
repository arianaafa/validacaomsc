<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00032Rule implements MscRuleInterface
{
    private const CODE = 'D1_00032';

    private const IC_FUNCAO_SUBFUNCAO = 'FS';

    private const PREFIXO_CONTA_DESPESA = '62213';

    private const MENSAGEM_INCONSISTENCIA = 'contas de despesa orçamentária sem o detalhamento de função/subfunção.';

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas do grupo 62213 exigem detalhamento de Função/Subfunção (FS).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if (! $this->isContaDespesaOrcamentaria($lineData->conta)) {
            return null;
        }

        $icMap = $this->extractIcMap($lineData->ics);
        $funcaoSubfuncao = trim($icMap[self::IC_FUNCAO_SUBFUNCAO] ?? '');

        if ($funcaoSubfuncao !== '') {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaDespesaOrcamentaria(string $conta): bool
    {
        return str_starts_with($this->normalizeConta($conta), self::PREFIXO_CONTA_DESPESA);
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

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }
}
