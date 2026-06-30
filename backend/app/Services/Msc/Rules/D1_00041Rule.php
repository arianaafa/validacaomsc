<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class D1_00041Rule implements MscRuleInterface
{
    private const CODE = 'D1_00041';

    private const IC_FUNCAO_SUBFUNCAO = 'FS';

    private const IC_CODIGO_ACOMPANHAMENTO = 'CO';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const TAMANHO_CONTA = 9;

    private const TAMANHO_FUNCAO = 2;

    private const FUNCAO_SAUDE = '10';

    private const CO_GENERICO_ZERADO = '0000';

    private const MENSAGEM_INCONSISTENCIA = 'despesa com ações e serviços públicos de saúde sem o código de acompanhamento orçamentário (CO) específico.';

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
     * Despesas de Saúde (função 10) nas contas 62 e 63 com saldo final não zerado
     * devem possuir Código de Acompanhamento Orçamentário (CO) válido e específico.
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

        if (! $this->isFuncaoSaude($icMap[self::IC_FUNCAO_SUBFUNCAO] ?? '')) {
            return null;
        }

        $codigoAcompanhamento = trim($icMap[self::IC_CODIGO_ACOMPANHAMENTO] ?? '');

        if ($this->isCodigoAcompanhamentoValido($codigoAcompanhamento)) {
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

    private function isFuncaoSaude(string $funcaoSubfuncao): bool
    {
        $funcaoSubfuncao = trim($funcaoSubfuncao);

        if ($funcaoSubfuncao === '') {
            return false;
        }

        $funcao = strlen($funcaoSubfuncao) >= self::TAMANHO_FUNCAO
            ? substr($funcaoSubfuncao, 0, self::TAMANHO_FUNCAO)
            : $funcaoSubfuncao;

        return $funcao === self::FUNCAO_SAUDE;
    }

    private function isCodigoAcompanhamentoValido(string $codigoAcompanhamento): bool
    {
        if ($codigoAcompanhamento === '') {
            return false;
        }

        return $codigoAcompanhamento !== self::CO_GENERICO_ZERADO;
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
