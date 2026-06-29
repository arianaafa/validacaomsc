<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00039Rule implements MscRuleInterface
{
    private const CODE = 'D1_00039';

    private const IC_FONTE_RECURSOS = 'FR';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const TAMANHO_CONTA = 9;

    private const TAMANHO_FONTE_RECURSOS = 4;

    private const DIGITO_RECURSOS_CONDICIONADOS = '9';

    private const MENSAGEM_INCONSISTENCIA = 'despesa orçamentária registrada com fonte de recursos condicionada (dígito 9 na segunda posição da fonte).';

    /**
     * Prefixos de execução da despesa orçamentária (classe 6, grupos 62 e 63).
     *
     * @var list<string>
     */
    private const PREFIXOS_CONTA_DESPESA = [
        '62',
        '63',
    ];

    public function getCode(): string
    {
        return self::CODE;
    }

    /**
     * Despesas orçamentárias (contas 62 e 63) com saldo final não zerado não devem
     * utilizar fontes de recursos condicionados (segundo dígito da FR igual a 9).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_FINAL) {
            return null;
        }

        if ($lineData->valor == 0.0) {
            return null;
        }

        if (! $this->isContaExecucaoDespesaOrcamentaria($lineData->conta)) {
            return null;
        }

        $icMap = $this->extractIcMap($lineData->ics);
        $fonteRecursos = trim($icMap[self::IC_FONTE_RECURSOS] ?? '');

        if (! $this->isFonteRecursosCondicionada($fonteRecursos)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaExecucaoDespesaOrcamentaria(string $conta): bool
    {
        $contaNormalizada = $this->normalizeConta($conta);

        if (strlen($contaNormalizada) !== self::TAMANHO_CONTA) {
            return false;
        }

        foreach (self::PREFIXOS_CONTA_DESPESA as $prefixo) {
            if (str_starts_with($contaNormalizada, $prefixo)) {
                return true;
            }
        }

        return false;
    }

    private function isFonteRecursosCondicionada(string $fonteRecursos): bool
    {
        if ($fonteRecursos === '' || ! ctype_digit($fonteRecursos)) {
            return false;
        }

        $fonteNormalizada = str_pad($fonteRecursos, self::TAMANHO_FONTE_RECURSOS, '0', STR_PAD_LEFT);

        if (strlen($fonteNormalizada) !== self::TAMANHO_FONTE_RECURSOS) {
            return false;
        }

        return $fonteNormalizada[1] === self::DIGITO_RECURSOS_CONDICIONADOS;
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

    private function normalizeTipoValor(string $tipoValor): string
    {
        return strtolower(trim($tipoValor));
    }
}
