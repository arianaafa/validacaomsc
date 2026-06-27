<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Enums\MscValidationErrorTipo;
use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleResultInterface;
use App\Services\Msc\Contracts\MscRuleValidationResult;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;
use RuntimeException;

final class D1_OrcamentariaContinuidadeRule implements MscRuleResultInterface, MscStatefulRuleInterface
{
    private const CODE_INCONSISTENCIA = 'D1_MOVIMENTACAO_INCONSISTENTE_ORCAMENTARIA';

    private const CODE_NOVA_COMBINACAO = 'D1_ORCAMENTARIA_NOVA_COMBINACAO';

    private const TIPO_SALDO_INICIAL = 'beginning_balance';

    private const TIPO_SALDO_FINAL_SICONFI = 'ending_balance';

    private const CLASSE_MINIMA = 5;

    private const CLASSE_MAXIMA = 6;

    private const TAMANHO_IC4 = 5;

    private const TAMANHO_FUNCAO = 2;

    private const TAMANHO_SUBFUNCAO = 3;

    private const TAMANHO_NATUREZA_DESPESA = 8;

    /**
     * @var list<string>
     */
    private const ORDEM_ATRIBUTOS_CHAVE = [
        'poder_orgao',
        'fonte_recursos',
        'funcao',
        'subfuncao',
        'natureza_despesa',
        'natureza_conta',
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

    public function __construct(
        private readonly SiconfiClient $siconfiClient,
    ) {}

    public function getCode(): string
    {
        return self::CODE_INCONSISTENCIA;
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
    }

    public function validate(MscLineData $lineData): ?string
    {
        return $this->validateResult($lineData)?->descricao;
    }

    public function validateResult(MscLineData $lineData): ?MscRuleValidationResult
    {
        if ($this->idEnte === '' || $this->tipoMatriz === '') {
            return null;
        }

        $classeConta = $this->resolveClasseOrcamentaria($lineData->conta);

        if ($classeConta === null) {
            return null;
        }

        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_INICIAL) {
            return null;
        }

        $atributos = $this->extrairAtributosOrcamentariosDoCsv($lineData);

        if (! $this->garantirCacheOrcamentario($classeConta)) {
            return new MscRuleValidationResult(
                codigoRegra: self::CODE_INCONSISTENCIA,
                descricao: sprintf(
                    'Conta %s (linha %d): não foi possível consultar o endpoint msc_orcamentaria do Siconfi para validar a continuidade do saldo inicial.',
                    $lineData->conta,
                    $lineData->linha,
                ),
                tipo: MscValidationErrorTipo::Erro,
            );
        }

        $chave = $this->buildChaveOrcamentaria($lineData->conta, $atributos);
        $atributosFormatados = $this->formatAtributosParaMensagem($atributos);

        $valorInformado = round($lineData->valor, 2);
        $valorHomologado = $this->saldoMesAnteriorPorChave[$chave] ?? null;

        if ($valorHomologado === null) {
            return new MscRuleValidationResult(
                codigoRegra: self::CODE_NOVA_COMBINACAO,
                descricao: sprintf(
                    'Conta %s (linha %d): combinação orçamentária [%s] não encontrada no mês anterior homologado no Siconfi (Tipo_valor %s). Pode indicar nova combinação de ICs. Valor informado (Tipo_valor %s): %.2f.',
                    $lineData->conta,
                    $lineData->linha,
                    $atributosFormatados,
                    self::TIPO_SALDO_FINAL_SICONFI,
                    self::TIPO_SALDO_INICIAL,
                    $valorInformado,
                ),
                tipo: MscValidationErrorTipo::Alerta,
            );
        }

        if ($valorInformado === $valorHomologado) {
            return null;
        }

        return new MscRuleValidationResult(
            codigoRegra: self::CODE_INCONSISTENCIA,
            descricao: sprintf(
                'Conta %s (linha %d): continuidade orçamentária inconsistente [%s]. Valor informado no CSV (Tipo_valor %s): %.2f. Valor homologado no mês anterior na API Orçamentária do Siconfi (Tipo_valor %s): %.2f. Diferença: %.2f.',
                $lineData->conta,
                $lineData->linha,
                $atributosFormatados,
                self::TIPO_SALDO_INICIAL,
                $valorInformado,
                self::TIPO_SALDO_FINAL_SICONFI,
                $valorHomologado,
                round($valorInformado - $valorHomologado, 2),
            ),
            tipo: MscValidationErrorTipo::Erro,
        );
    }

    private function garantirCacheOrcamentario(int $classeConta): bool
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
                $tipoValor = $item['tipo_valor'] ?? null;

                if (! is_string($tipoValor) || $this->normalizeTipoValor($tipoValor) !== self::TIPO_SALDO_FINAL_SICONFI) {
                    continue;
                }

                $contaContabil = $item['conta_contabil'] ?? null;
                $valor = $item['valor'] ?? null;

                if (! is_string($contaContabil) || ! is_numeric($valor)) {
                    continue;
                }

                $atributos = $this->extrairAtributosOrcamentariosDaApi($item);
                $chave = $this->buildChaveOrcamentaria($contaContabil, $atributos);

                $this->saldoMesAnteriorPorChave[$chave] = round((float) $valor, 2);
            }

            $this->cacheCarregadoPorClasse[$classeConta] = true;

            return true;
        } catch (RuntimeException) {
            $this->cacheCarregadoPorClasse[$classeConta] = true;
            $this->cacheFalhouPorClasse[$classeConta] = true;

            return false;
        }
    }

    /**
     * @param array<string, string> $atributos
     */
    private function buildChaveOrcamentaria(string $contaContabil, array $atributos): string
    {
        $partes = [$this->normalizeConta($contaContabil)];

        foreach (self::ORDEM_ATRIBUTOS_CHAVE as $campo) {
            $valor = trim($atributos[$campo] ?? '');

            if ($valor === '') {
                continue;
            }

            $partes[] = $valor;
        }

        return implode('_', $partes);
    }

    /**
     * @return array<string, string>
     */
    private function extrairAtributosOrcamentariosDoCsv(MscLineData $lineData): array
    {
        $atributos = [];

        $poderOrgao = trim($lineData->ics['IC1'] ?? '');

        if ($poderOrgao !== '') {
            $atributos['poder_orgao'] = $poderOrgao;
        }

        $fonteRecursos = trim($lineData->ics['IC2'] ?? '');

        if ($fonteRecursos !== '') {
            $atributos['fonte_recursos'] = $fonteRecursos;
        }

        [$funcao, $subfuncao] = $this->extrairFuncaoSubfuncaoDeIc4(trim($lineData->ics['IC4'] ?? ''));

        if ($funcao !== '') {
            $atributos['funcao'] = $funcao;
        }

        if ($subfuncao !== '') {
            $atributos['subfuncao'] = $subfuncao;
        }

        $naturezaDespesa = $this->extrairNaturezaDespesaDeIc3(trim($lineData->ics['IC3'] ?? ''));

        if ($naturezaDespesa !== '') {
            $atributos['natureza_despesa'] = $naturezaDespesa;
        }

        $naturezaConta = strtoupper(trim($lineData->naturezaValor));

        if (in_array($naturezaConta, ['D', 'C'], true)) {
            $atributos['natureza_conta'] = $naturezaConta;
        }

        return $atributos;
    }

    /**
     * @param array<string, bool|float|int|string|null> $item
     *
     * @return array<string, string>
     */
    private function extrairAtributosOrcamentariosDaApi(array $item): array
    {
        $atributos = [];

        $poderOrgao = $this->normalizarAtributoApi($item['poder_orgao'] ?? null);

        if ($poderOrgao !== null) {
            $atributos['poder_orgao'] = $poderOrgao;
        }

        $fonteRecursos = $this->normalizarAtributoApi($item['fonte_recursos'] ?? null);

        if ($fonteRecursos !== null) {
            $atributos['fonte_recursos'] = $fonteRecursos;
        }

        $funcao = $this->normalizarAtributoApi($item['funcao'] ?? null);

        if ($funcao !== null) {
            $atributos['funcao'] = $funcao;
        }

        $subfuncao = $this->normalizarAtributoApi($item['subfuncao'] ?? null);

        if ($subfuncao !== null) {
            $atributos['subfuncao'] = $subfuncao;
        }

        $naturezaDespesa = $this->normalizarAtributoApi($item['natureza_despesa'] ?? null);

        if ($naturezaDespesa !== null) {
            $atributos['natureza_despesa'] = $naturezaDespesa;
        }

        $naturezaConta = $item['natureza_conta'] ?? null;

        if (is_string($naturezaConta)) {
            $naturezaNormalizada = strtoupper(trim($naturezaConta));

            if (in_array($naturezaNormalizada, ['D', 'C'], true)) {
                $atributos['natureza_conta'] = $naturezaNormalizada;
            }
        }

        return $atributos;
    }

    /**
     * @param array<string, string> $atributos
     */
    private function formatAtributosParaMensagem(array $atributos): string
    {
        $rotulos = [
            'poder_orgao' => 'PO',
            'fonte_recursos' => 'FR',
            'funcao' => 'Função',
            'subfuncao' => 'Subfunção',
            'natureza_despesa' => 'ND',
            'natureza_conta' => 'Natureza',
        ];

        $partes = [];

        foreach (self::ORDEM_ATRIBUTOS_CHAVE as $campo) {
            $valor = trim($atributos[$campo] ?? '');

            if ($valor === '') {
                continue;
            }

            $partes[] = sprintf('%s=%s', $rotulos[$campo], $valor);
        }

        if ($partes === []) {
            return 'conta sem atributos complementares';
        }

        return implode(', ', $partes);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function extrairFuncaoSubfuncaoDeIc4(string $ic4): array
    {
        if ($ic4 === '') {
            return ['', ''];
        }

        $funcao = strlen($ic4) >= self::TAMANHO_FUNCAO
            ? substr($ic4, 0, self::TAMANHO_FUNCAO)
            : $ic4;

        $subfuncao = strlen($ic4) >= self::TAMANHO_IC4
            ? substr($ic4, self::TAMANHO_FUNCAO, self::TAMANHO_SUBFUNCAO)
            : (strlen($ic4) > self::TAMANHO_FUNCAO ? substr($ic4, self::TAMANHO_FUNCAO) : '');

        return [$funcao, $subfuncao];
    }

    private function extrairNaturezaDespesaDeIc3(string $ic3): string
    {
        if ($ic3 === '') {
            return '';
        }

        if (strlen($ic3) >= self::TAMANHO_NATUREZA_DESPESA) {
            return substr($ic3, 0, self::TAMANHO_NATUREZA_DESPESA);
        }

        return $ic3;
    }

    private function normalizarAtributoApi(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_string($valor)) {
            $valorNormalizado = trim($valor);

            return $valorNormalizado === '' ? null : $valorNormalizado;
        }

        if (is_int($valor) || is_float($valor)) {
            return (string) $valor;
        }

        return null;
    }

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }

    private function resolveClasseOrcamentaria(string $conta): ?int
    {
        $contaNormalizada = $this->normalizeConta($conta);
        $primeiroDigito = $contaNormalizada[0] ?? '';

        if ($primeiroDigito === '' || ! ctype_digit($primeiroDigito)) {
            return null;
        }

        $classe = (int) $primeiroDigito;

        if ($classe < self::CLASSE_MINIMA || $classe > self::CLASSE_MAXIMA) {
            return null;
        }

        return $classe;
    }

    private function normalizeTipoValor(string $tipoValor): string
    {
        return strtolower(trim($tipoValor));
    }
}
