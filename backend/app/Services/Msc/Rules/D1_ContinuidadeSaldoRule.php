<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;
use RuntimeException;

final class D1_ContinuidadeSaldoRule implements MscStatefulRuleInterface
{
    private const CODE = 'D1_MOVIMENTACAO_INCONSISTENTE';

    private const TIPO_SALDO_INICIAL = 'beginning_balance';

    private const TIPO_MOVIMENTACAO = 'period_change';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const FLOAT_EPSILON = 0.01;

    /**
     * @var array<string, string>
     */
    private const API_FIELD_TO_TIPO = [
        'poder_orgao' => 'PO',
        'financeiro_permanente' => 'FP',
        'fonte_recursos' => 'FR',
        'divida_consolidada' => 'DC',
        'complemento_fonte' => 'CF',
        'natureza_receita' => 'NR',
        'natureza_despesa' => 'ND',
        'funcao' => 'FN',
        'subfuncao' => 'SF',
        'educacao_saude' => 'ES',
        'ano_inscricao' => 'AI',
        'ano_fonte_recursos' => 'AF',
    ];

    private string $idEnte = '';

    private int $ano = 0;

    private int $mes = 0;

    private string $tipoMatriz = '';

    /**
     * @var array<string, float>
     */
    private array $saldoMesAnteriorPorChave = [];

    /**
     * @var array<int, bool>
     */
    private array $cacheCarregadoPorClasse = [];

    /**
     * @var array<int, bool>
     */
    private array $cacheFalhouPorClasse = [];

    /**
     * @var array<string, array{beginning_balance: float|null, period_change: float|null, ending_balance: float|null}>
     */
    private array $valoresArquivoPorChave = [];

    /**
     * @var array<string, bool>
     */
    private array $consistenciaValidadaPorChave = [];

    public function __construct(
        private readonly SiconfiClient $siconfiClient,
    ) {}

    public function getCode(): string
    {
        return self::CODE;
    }

    public function prepare(string $idEnte, int $ano, int $mes, string $tipoMatriz): void
    {
        $this->reset();
        $this->idEnte = $idEnte;
        $this->ano = $ano;
        $this->mes = $mes;
        $this->tipoMatriz = $tipoMatriz;
    }

    public function reset(): void
    {
        $this->idEnte = '';
        $this->ano = 0;
        $this->mes = 0;
        $this->tipoMatriz = '';
        $this->saldoMesAnteriorPorChave = [];
        $this->cacheCarregadoPorClasse = [];
        $this->cacheFalhouPorClasse = [];
        $this->valoresArquivoPorChave = [];
        $this->consistenciaValidadaPorChave = [];
    }

    public function validate(MscLineData $lineData): ?string
    {
        if ($this->idEnte === '' || $this->tipoMatriz === '') {
            return null;
        }

        $tipoValor = strtolower(trim($lineData->tipoValor));

        if (! in_array($tipoValor, [self::TIPO_SALDO_INICIAL, self::TIPO_MOVIMENTACAO, self::TIPO_SALDO_FINAL], true)) {
            return null;
        }

        $classeConta = $this->resolveClasseConta($lineData->conta);

        if ($classeConta === null || $classeConta <= 4) {
            return null;
        }

        $chave = $this->buildChave($lineData->conta, $lineData->ics);
        $this->registrarValorArquivo($chave, $tipoValor, $lineData->valor);

        if ($tipoValor === self::TIPO_SALDO_INICIAL) {
            return $this->validarContinuidade(
                $lineData,
                $classeConta,
                $chave,
                $lineData->valor,
            );
        }

        if ($tipoValor === self::TIPO_SALDO_FINAL) {
            return $this->validarConsistenciaInterna($lineData, $chave);
        }

        return null;
    }

    private function validarContinuidade(
        MscLineData $lineData,
        int $classeConta,
        string $chave,
        float $saldoInicialInformado,
    ): ?string {
        if (! $this->garantirCacheMesAnterior($classeConta)) {
            return sprintf(
                'Conta %s (linha %d): não foi possível consultar o saldo homologado do mês anterior no Siconfi para validar a continuidade.',
                $lineData->conta,
                $lineData->linha,
            );
        }

        $saldoFinalMesAnterior = $this->saldoMesAnteriorPorChave[$chave] ?? null;

        if ($saldoFinalMesAnterior === null) {
            return sprintf(
                'Conta %s (linha %d): saldo inicial informado (%.2f) diverge do saldo final homologado no Siconfi do mês anterior (não encontrado para a combinação de ICs).',
                $lineData->conta,
                $lineData->linha,
                $saldoInicialInformado,
            );
        }

        if (! $this->valoresSaoEquivalentes($saldoInicialInformado, $saldoFinalMesAnterior)) {
            return sprintf(
                'Conta %s (linha %d): saldo inicial informado (%.2f) diverge do saldo final homologado no Siconfi do mês anterior (%.2f).',
                $lineData->conta,
                $lineData->linha,
                $saldoInicialInformado,
                $saldoFinalMesAnterior,
            );
        }

        return null;
    }

    private function validarConsistenciaInterna(MscLineData $lineData, string $chave): ?string
    {
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
            'Conta %s (linha %d): movimentação inconsistente. Esperado saldo final %.2f (saldo inicial %.2f + movimentação %.2f), informado %.2f.',
            $lineData->conta,
            $lineData->linha,
            $saldoFinalEsperado,
            $saldoInicial,
            $movimentacao,
            $saldoFinal,
        );
    }

    private function garantirCacheMesAnterior(int $classeConta): bool
    {
        if (isset($this->cacheCarregadoPorClasse[$classeConta])) {
            return ! ($this->cacheFalhouPorClasse[$classeConta] ?? false);
        }

        try {
            $items = $this->siconfiClient->getMscMesAnterior(
                $this->idEnte,
                $this->ano,
                $this->mes,
                $this->tipoMatriz,
                $classeConta,
            );

            foreach ($items as $item) {
                $contaContabil = $item['conta_contabil'] ?? null;
                $valor = $item['valor'] ?? null;

                if (! is_string($contaContabil) || ! is_numeric($valor)) {
                    continue;
                }

                $itemChave = $this->buildChaveFromApiItem($contaContabil, $item);
                $this->saldoMesAnteriorPorChave[$itemChave] = (float) $valor;
            }

            $this->cacheCarregadoPorClasse[$classeConta] = true;

            return true;
        } catch (RuntimeException) {
            $this->cacheCarregadoPorClasse[$classeConta] = true;
            $this->cacheFalhouPorClasse[$classeConta] = true;

            return false;
        }
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
     */
    private function buildChave(string $conta, array $ics): string
    {
        return $this->normalizeConta($conta).'|'.$this->buildHashIcsFromCsv($ics);
    }

    /**
     * @param array<string, bool|float|int|string|null> $item
     */
    private function buildChaveFromApiItem(string $contaContabil, array $item): string
    {
        return $this->normalizeConta($contaContabil).'|'.$this->buildHashIcsFromApiItem($item);
    }

    /**
     * @param array<string, string> $ics
     */
    private function buildHashIcsFromCsv(array $ics): string
    {
        $pares = [];

        for ($indice = 1; $indice <= 6; $indice++) {
            $valorIc = trim($ics["IC{$indice}"] ?? '');
            $tipoIc = strtoupper(trim($ics["TIPO{$indice}"] ?? ''));

            if ($valorIc === '' || $tipoIc === '') {
                continue;
            }

            $pares[$tipoIc] = $valorIc;
        }

        return $this->buildHashFromPares($pares);
    }

    /**
     * @param array<string, bool|float|int|string|null> $item
     */
    private function buildHashIcsFromApiItem(array $item): string
    {
        $pares = [];

        foreach (self::API_FIELD_TO_TIPO as $campoApi => $tipoIc) {
            if (! array_key_exists($campoApi, $item)) {
                continue;
            }

            $valor = $item[$campoApi];

            if ($valor === null || $valor === '') {
                continue;
            }

            $pares[$tipoIc] = (string) $valor;
        }

        return $this->buildHashFromPares($pares);
    }

    /**
     * @param array<string, string> $pares
     */
    private function buildHashFromPares(array $pares): string
    {
        ksort($pares);

        $partes = [];

        foreach ($pares as $tipo => $valor) {
            $partes[] = $tipo.':'.$valor;
        }

        return hash('xxh128', implode('|', $partes));
    }

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }

    private function resolveClasseConta(string $conta): ?int
    {
        $contaNormalizada = $this->normalizeConta($conta);
        $primeiroDigito = $contaNormalizada[0] ?? '';

        if ($primeiroDigito === '' || ! ctype_digit($primeiroDigito)) {
            return null;
        }

        $classe = (int) $primeiroDigito;

        if ($classe < 1 || $classe > 8) {
            return null;
        }

        return $classe;
    }

    private function valoresSaoEquivalentes(float $esperado, float $informado): bool
    {
        return abs($esperado - $informado) <= self::FLOAT_EPSILON;
    }
}
