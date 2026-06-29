<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00044Rule implements MscRuleInterface
{
    private const CODE = 'D1_00044';

    private const IC_ANO_INSCRICAO = 'AI';

    private const MENSAGEM_INCONSISTENCIA = 'contas de restos a pagar sem a informação complementar de ano de inscrição (AI).';

    /**
     * Contas de Restos a Pagar sujeitas à obrigatoriedade de AI.
     *
     * @var list<string>
     */
    private const CONTAS_RESTOS_A_PAGAR = [
        '531100000',
        '531200000',
        '531600000',
        '531700000',
        '532100000',
        '532200000',
        '532600000',
        '532700000',
        '631100000',
        '631200000',
        '631300000',
        '631400000',
        '631500000',
        '631600000',
        '631710000',
        '631720000',
        '631910000',
        '631990000',
        '632100000',
        '632200000',
        '632600000',
        '632700000',
        '632910000',
        '632990000',
    ];

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Contas de RP listadas exigem detalhamento de Ano de Inscrição (AI).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if (! $this->isContaRestosAPagar($lineData->conta)) {
            return null;
        }

        $icMap = $this->extractIcMap($lineData->ics);
        $anoInscricao = trim($icMap[self::IC_ANO_INSCRICAO] ?? '');

        if ($anoInscricao !== '') {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaRestosAPagar(string $conta): bool
    {
        return in_array($this->normalizeConta($conta), self::CONTAS_RESTOS_A_PAGAR, true);
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
