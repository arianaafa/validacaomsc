<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;

final class D1_00018Rule implements MscStatefulRuleInterface
{
    private const CODE = 'D1_00018';

    private const TIPO_SALDO_INICIAL = 'beginning_balance';

    private const TIPO_MOVIMENTACAO = 'period_change';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const FLOAT_EPSILON = 0.01;

    private const MENSAGEM_INCONSISTENCIA = 'Movimentação inconsistente: saldo inicial + movimentação difere do saldo final para o conjunto de informações complementares.';

    /**
     * Ordem de resolução do conjunto de IC (do mais específico ao mais simples).
     *
     * @var list<string>
     */
    private const LAYOUT_ORDEM = ['09', '07', '08', '06', '04', '05', '03', '02', '01'];

    /**
     * Campos de IC que compõem a chave de agrupamento por conjunto.
     *
     * @var array<string, list<string>>
     */
    private const LAYOUT_CAMPOS = [
        '01' => ['PO'],
        '02' => ['PO', 'FP'],
        '03' => ['PO', 'FP', 'DC'],
        '04' => ['PO', 'FP', 'FR', 'CO'],
        '05' => ['PO', 'FR', 'CO'],
        '06' => ['PO', 'FR', 'CO', 'NR'],
        '07' => ['PO', 'FS', 'FR', 'CO', 'ND'],
        '08' => ['PO', 'FP', 'DC', 'FR'],
        '09' => ['PO', 'FS', 'FR', 'CO', 'ND', 'AI'],
    ];

    /**
     * @var array<string, array{beginning_balance: float|null, period_change: float|null, ending_balance: float|null}>
     */
    private array $valoresArquivoPorChave = [];

    /**
     * @var array<string, bool>
     */
    private array $consistenciaValidadaPorChave = [];

    public function getCode(): string
    {
        return self::CODE;
    }

    public function prepare(string $idEnte, int $ano, int $mes, string $tipoMatriz): void
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->valoresArquivoPorChave = [];
        $this->consistenciaValidadaPorChave = [];
    }

    public function validate(MscLineData $lineData): ?string
    {
        $tipoValor = strtolower(trim($lineData->tipoValor));

        if (! in_array($tipoValor, [self::TIPO_SALDO_INICIAL, self::TIPO_MOVIMENTACAO, self::TIPO_SALDO_FINAL], true)) {
            return null;
        }

        $icMap = $this->extractIcMap($lineData->ics);
        $layout = $this->resolveLayoutCodigo($icMap);
        $chave = $this->buildGroupKey($lineData->conta, $layout, $icMap);

        $this->registrarValorArquivo($chave, $tipoValor, $lineData->valor);

        if ($tipoValor !== self::TIPO_SALDO_FINAL) {
            return null;
        }

        return $this->validarConsistenciaInterna($lineData, $chave, $layout, $icMap);
    }

    private function validarConsistenciaInterna(
        MscLineData $lineData,
        string $chave,
        ?string $layout,
        array $icMap,
    ): ?string {
        if (isset($this->consistenciaValidadaPorChave[$chave])) {
            return null;
        }

        $valores = $this->valoresArquivoPorChave[$chave] ?? null;

        if ($valores === null) {
            return null;
        }

        $saldoInicial = $valores['beginning_balance'];
        $movimentacao = $valores['period_change'];
        $saldoFinal = $valores['ending_balance'];

        if ($saldoInicial === null || $movimentacao === null || $saldoFinal === null) {
            return null;
        }

        $this->consistenciaValidadaPorChave[$chave] = true;
        $saldoFinalEsperado = $saldoInicial + $movimentacao;

        if ($this->valoresSaoEquivalentes($saldoFinalEsperado, $saldoFinal)) {
            return null;
        }

        return sprintf(
            'Conta %s (linha %d) [%s]: %s',
            $lineData->conta,
            $lineData->linha,
            $this->formatChaveIc($layout, $icMap),
            self::MENSAGEM_INCONSISTENCIA,
        );
    }

    private function registrarValorArquivo(string $chave, string $tipoValor, float $valor): void
    {
        if (! isset($this->valoresArquivoPorChave[$chave])) {
            $this->valoresArquivoPorChave[$chave] = [
                'beginning_balance' => null,
                'period_change' => null,
                'ending_balance' => null,
            ];
        }

        $this->valoresArquivoPorChave[$chave][$tipoValor] = $valor;
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

    /**
     * @param array<string, string> $icMap
     */
    private function resolveLayoutCodigo(array $icMap): ?string
    {
        if ($icMap === []) {
            return null;
        }

        if (! isset($icMap['PO']) || $icMap['PO'] === '') {
            return null;
        }

        foreach (self::LAYOUT_ORDEM as $codigoLayout) {
            if ($this->layoutAtendeCampos($codigoLayout, $icMap)) {
                return $codigoLayout;
            }
        }

        return '01';
    }

    /**
     * @param array<string, string> $icMap
     */
    private function layoutAtendeCampos(string $codigoLayout, array $icMap): bool
    {
        foreach (self::LAYOUT_CAMPOS[$codigoLayout] as $campo) {
            if (! isset($icMap[$campo]) || $icMap[$campo] === '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, string> $icMap
     */
    private function buildGroupKey(string $conta, ?string $layout, array $icMap): string
    {
        $contaNormalizada = $this->normalizeConta($conta);

        if ($layout !== null) {
            $partes = [$contaNormalizada, 'layout:'.$layout];

            foreach (self::LAYOUT_CAMPOS[$layout] as $campo) {
                $partes[] = $campo.':'.($icMap[$campo] ?? '');
            }

            return implode('|', $partes);
        }

        if ($icMap === []) {
            return $contaNormalizada;
        }

        return $contaNormalizada.'|'.$this->buildHashFromIcMap($icMap);
    }

    /**
     * @param array<string, string> $icMap
     */
    private function formatChaveIc(?string $layout, array $icMap): string
    {
        if ($layout !== null) {
            $partes = ['Conjunto IC '.$layout];

            foreach (self::LAYOUT_CAMPOS[$layout] as $campo) {
                $valor = $icMap[$campo] ?? '';

                if ($valor === '') {
                    continue;
                }

                $partes[] = $campo.'='.$valor;
            }

            return implode(', ', $partes);
        }

        if ($icMap === []) {
            return 'sem informações complementares';
        }

        ksort($icMap);

        $partes = [];

        foreach ($icMap as $campo => $valor) {
            $partes[] = $campo.'='.$valor;
        }

        return implode(', ', $partes);
    }

    /**
     * @param array<string, string> $icMap
     */
    private function buildHashFromIcMap(array $icMap): string
    {
        ksort($icMap);

        $partes = [];

        foreach ($icMap as $tipo => $valor) {
            $partes[] = $tipo.':'.$valor;
        }

        return hash('xxh128', implode('|', $partes));
    }

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }

    private function valoresSaoEquivalentes(float $esperado, float $informado): bool
    {
        return abs($esperado - $informado) <= self::FLOAT_EPSILON;
    }
}
