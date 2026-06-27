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

final class D1_ControleContinuidadeRule implements MscRuleResultInterface, MscStatefulRuleInterface
{
    private const CODE_INCONSISTENCIA = 'D1_MOVIMENTACAO_INCONSISTENTE_CONTROLE';

    private const TIPO_SALDO_INICIAL = 'beginning_balance';

    private const TIPO_SALDO_FINAL_SICONFI = 'ending_balance';

    private const CLASSE_MINIMA = 7;

    private const CLASSE_MAXIMA = 8;

    private const TAMANHO_IC4 = 5;

    private const TAMANHO_FUNCAO = 2;

    private const TAMANHO_SUBFUNCAO = 3;

    private const TAMANHO_NATUREZA_DESPESA = 8;

    private const CODE_NOVA_COMBINACAO = 'D1_CONTROLE_NOVA_COMBINACAO';

    /**
     * @var list<string>
     */
    private const ORDEM_ATRIBUTOS_CHAVE = [
        'poder_orgao',
        'fonte_recursos',
        'complemento_fonte',
        'funcao',
        'subfuncao',
        'natureza_despesa',
        'ano_inscricao',
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

        $classeConta = $this->resolveClasseControle($lineData->conta);

        if ($classeConta === null) {
            return null;
        }

        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_INICIAL) {
            return null;
        }

        $atributos = $this->extrairAtributosControleDoCsv($lineData);

        if (! $this->garantirCacheControle($classeConta)) {
            return new MscRuleValidationResult(
                codigoRegra: self::CODE_INCONSISTENCIA,
                descricao: sprintf(
                    'Conta %s (linha %d): não foi possível consultar o endpoint msc_controle do Siconfi para validar a continuidade do saldo inicial.',
                    $lineData->conta,
                    $lineData->linha,
                ),
                tipo: MscValidationErrorTipo::Erro,
            );
        }

        $chave = $this->buildChaveControle($lineData->conta, $atributos);
        $atributosFormatados = $this->formatAtributosParaMensagem($atributos);

        $valorInformado = round($lineData->valor, 2);
        $valorHomologado = $this->saldoMesAnteriorPorChave[$chave] ?? null;

        if ($valorHomologado === null) {
            return new MscRuleValidationResult(
                codigoRegra: self::CODE_NOVA_COMBINACAO,
                descricao: sprintf(
                    'Conta %s (linha %d): combinação de controle [%s] não encontrada no mês anterior homologado no Siconfi (Tipo_valor %s). Pode indicar nova combinação de ICs. Valor informado (Tipo_valor %s): %.2f.',
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
                'Conta %s (linha %d): continuidade de controle inconsistente [%s]. Valor informado no CSV (Tipo_valor %s): %.2f. Valor homologado no mês anterior na API de Controle do Siconfi (Tipo_valor %s): %.2f. Diferença: %.2f.',
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

    private function garantirCacheControle(int $classeConta): bool
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

                $atributos = $this->extrairAtributosControleDaApi($item);
                $chave = $this->buildChaveControle($contaContabil, $atributos);

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
    private function buildChaveControle(string $contaContabil, array $atributos): string
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
    private function extrairAtributosControleDoCsv(MscLineData $lineData): array
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

        $complementoFonte = $this->normalizeComplementoFonte($lineData->ics['IC3'] ?? null);

        if ($complementoFonte !== '') {
            $atributos['complemento_fonte'] = $complementoFonte;
        }

        $naturezaDespesa = $this->extrairNaturezaDespesa(trim($lineData->ics['IC4'] ?? ''));

        if ($naturezaDespesa !== '') {
            $atributos['natureza_despesa'] = $naturezaDespesa;
        }

        [$funcao, $subfuncao] = $this->extrairFuncaoSubfuncao(trim($lineData->ics['IC5'] ?? ''));

        if ($funcao !== '') {
            $atributos['funcao'] = $funcao;
        }

        if ($subfuncao !== '') {
            $atributos['subfuncao'] = $subfuncao;
        }

        $anoInscricao = trim($lineData->ics['IC6'] ?? '');

        if ($anoInscricao !== '') {
            $atributos['ano_inscricao'] = $anoInscricao;
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
    private function extrairAtributosControleDaApi(array $item): array
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

        $complementoFonte = $this->normalizarAtributoApi($item['complemento_fonte'] ?? null);

        if ($complementoFonte !== null) {
            $atributos['complemento_fonte'] = $complementoFonte;
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

        $anoInscricao = $this->normalizarAtributoApi($item['ano_inscricao'] ?? null);

        if ($anoInscricao !== null) {
            $atributos['ano_inscricao'] = $anoInscricao;
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
            'complemento_fonte' => 'CF',
            'funcao' => 'Função',
            'subfuncao' => 'Subfunção',
            'natureza_despesa' => 'ND',
            'ano_inscricao' => 'AI',
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
    private function extrairFuncaoSubfuncao(string $icFuncaoSubfuncao): array
    {
        if ($icFuncaoSubfuncao === '') {
            return ['', ''];
        }

        $funcao = strlen($icFuncaoSubfuncao) >= self::TAMANHO_FUNCAO
            ? substr($icFuncaoSubfuncao, 0, self::TAMANHO_FUNCAO)
            : $icFuncaoSubfuncao;

        $subfuncao = strlen($icFuncaoSubfuncao) >= self::TAMANHO_IC4
            ? substr($icFuncaoSubfuncao, self::TAMANHO_FUNCAO, self::TAMANHO_SUBFUNCAO)
            : (strlen($icFuncaoSubfuncao) > self::TAMANHO_FUNCAO ? substr($icFuncaoSubfuncao, self::TAMANHO_FUNCAO) : '');

        return [$funcao, $subfuncao];
    }

    private function extrairNaturezaDespesa(string $icNaturezaDespesa): string
    {
        if ($icNaturezaDespesa === '') {
            return '';
        }

        if (strlen($icNaturezaDespesa) >= self::TAMANHO_NATUREZA_DESPESA) {
            return substr($icNaturezaDespesa, 0, self::TAMANHO_NATUREZA_DESPESA);
        }

        return $icNaturezaDespesa;
    }

    private function normalizeComplementoFonte(string|int|float|null $complementoFonte): string
    {
        if ($complementoFonte === null) {
            return '';
        }

        return trim((string) $complementoFonte);
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

    private function resolveClasseControle(string $conta): ?int
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
