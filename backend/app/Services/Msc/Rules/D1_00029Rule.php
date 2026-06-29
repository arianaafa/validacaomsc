<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00029Rule implements MscRuleInterface
{
    private const CODE = 'D1_00029';

    private const IC_FONTE_RECURSOS = 'FR';

    private const MENSAGEM_INCONSISTENCIA = 'contas de receita orçamentária e deduções sem detalhamento de fonte ou destinação de recurso.';

    /**
     * Grupos de receita orçamentária e deduções (PCASP Estendido).
     *
     * @var list<string>
     */
    private const PREFIXOS_CONTA_RECEITA = [
        '6211',
        '6212',
        '6213',
    ];

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas dos grupos 6211, 6212 e 6213 exigem detalhamento de Fonte de Recursos (FR).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if (! $this->isContaReceitaOrcamentaria($lineData->conta)) {
            return null;
        }

        $icMap = $this->extractIcMap($lineData->ics);
        $fonteRecursos = trim($icMap[self::IC_FONTE_RECURSOS] ?? '');

        if ($fonteRecursos !== '') {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaReceitaOrcamentaria(string $conta): bool
    {
        $contaNormalizada = $this->normalizeConta($conta);

        foreach (self::PREFIXOS_CONTA_RECEITA as $prefixo) {
            if (str_starts_with($contaNormalizada, $prefixo)) {
                return true;
            }
        }

        return false;
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
