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

final class D1_PatrimonialContinuidadeRule implements MscRuleResultInterface, MscStatefulRuleInterface
{
    private const CODE_INCONSISTENCIA = 'D1_SALDO_INICIAL_INCONSISTENTE_PATRIMONIAL';

    private const CODE_NOVA_COMBINACAO = 'D1_PATRIMONIAL_NOVA_COMBINACAO';

    private const TIPO_VALOR_PLANILHA = 'beginning_balance';

    private const TIPO_VALOR_SICONFI = 'ending_balance';

    private const CLASSE_MINIMA = 1;

    private const CLASSE_MAXIMA = 4;

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

        $classeConta = $this->resolveClassePatrimonial($lineData->conta);

        if ($classeConta === null) {
            return null;
        }

        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_VALOR_PLANILHA) {
            return null;
        }

        $atributosPatrimoniais = $this->extrairAtributosPatrimoniaisDoCsv($lineData);

        if (! $this->garantirCachePatrimonial($classeConta)) {
            return new MscRuleValidationResult(
                codigoRegra: self::CODE_INCONSISTENCIA,
                descricao: sprintf(
                    'Conta %s (linha %d): não foi possível consultar o endpoint msc_patrimonial do Siconfi para validar a continuidade do saldo inicial.',
                    $lineData->conta,
                    $lineData->linha,
                ),
                tipo: MscValidationErrorTipo::Erro,
            );
        }

        $chave = $this->buildChavePatrimonial($lineData->conta, $atributosPatrimoniais);
        $atributosFormatados = $this->formatAtributosParaMensagem($atributosPatrimoniais);

        $valorInformado = round($lineData->valor, 2);
        $valorHomologado = $this->saldoMesAnteriorPorChave[$chave] ?? null;

        if ($valorHomologado === null) {
            return new MscRuleValidationResult(
                codigoRegra: self::CODE_NOVA_COMBINACAO,
                descricao: sprintf(
                    'Conta %s (linha %d): combinação patrimonial [%s] não encontrada no mês anterior homologado no Siconfi (Tipo_valor %s). Pode indicar nova conta corrente/fonte. Valor informado (Tipo_valor %s): %.2f.',
                    $lineData->conta,
                    $lineData->linha,
                    $atributosFormatados,
                    self::TIPO_VALOR_SICONFI,
                    self::TIPO_VALOR_PLANILHA,
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
                'Conta %s (linha %d): continuidade patrimonial inconsistente [%s]. Valor informado (Tipo_valor %s): %.2f. Valor homologado no mês anterior (Siconfi, Tipo_valor %s): %.2f. Diferença: %.2f.',
                $lineData->conta,
                $lineData->linha,
                $atributosFormatados,
                self::TIPO_VALOR_PLANILHA,
                $valorInformado,
                self::TIPO_VALOR_SICONFI,
                $valorHomologado,
                round($valorInformado - $valorHomologado, 2),
            ),
            tipo: MscValidationErrorTipo::Erro,
        );
    }

    /**
     * @var list<string>
     */
    private const ORDEM_ATRIBUTOS_CHAVE = [
        'poder_orgao',
        'financeiro_permanente',
        'fonte_recursos',
        'complemento_fonte',
        'natureza_conta',
    ];

    private function garantirCachePatrimonial(int $classeConta): bool
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

                if (! is_string($tipoValor) || $this->normalizeTipoValor($tipoValor) !== self::TIPO_VALOR_SICONFI) {
                    continue;
                }

                $contaContabil = $item['conta_contabil'] ?? null;
                $valor = $item['valor'] ?? null;

                if (! is_string($contaContabil) || ! is_numeric($valor)) {
                    continue;
                }

                $atributos = $this->extrairAtributosPatrimoniaisDaApi($item);

                $chave = $this->buildChavePatrimonial($contaContabil, $atributos);

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
    private function buildChavePatrimonial(string $contaContabil, array $atributos): string
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
    private function extrairAtributosPatrimoniaisDoCsv(MscLineData $lineData): array
    {
        $atributos = [];

        $poderOrgao = trim($lineData->ics['IC1'] ?? '');

        if ($poderOrgao !== '') {
            $atributos['poder_orgao'] = $poderOrgao;
        }

        $financeiroPermanente = trim($lineData->ics['IC2'] ?? '');

        if ($financeiroPermanente !== '') {
            $atributos['financeiro_permanente'] = $financeiroPermanente;
        }

        $fonteRecursos = trim($lineData->ics['IC3'] ?? '');

        if ($fonteRecursos !== '') {
            $atributos['fonte_recursos'] = $fonteRecursos;
        }

        $complementoFonte = $this->normalizeComplementoFonte($lineData->ics['IC4'] ?? null);

        if ($complementoFonte !== '') {
            $atributos['complemento_fonte'] = $complementoFonte;
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
    private function extrairAtributosPatrimoniaisDaApi(array $item): array
    {
        $atributos = [];

        $poderOrgao = $this->normalizarAtributoApi($item['poder_orgao'] ?? null);

        if ($poderOrgao !== null) {
            $atributos['poder_orgao'] = $poderOrgao;
        }

        $financeiroPermanente = $this->normalizarAtributoApi($item['financeiro_permanente'] ?? null);

        if ($financeiroPermanente !== null) {
            $atributos['financeiro_permanente'] = $financeiroPermanente;
        }

        $fonteRecursos = $this->normalizarAtributoApi($item['fonte_recursos'] ?? null);

        if ($fonteRecursos !== null) {
            $atributos['fonte_recursos'] = $fonteRecursos;
        }

        $complementoFonte = $this->normalizarAtributoApi($item['complemento_fonte'] ?? null);

        if ($complementoFonte !== null) {
            $atributos['complemento_fonte'] = $complementoFonte;
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
            'financeiro_permanente' => 'FP',
            'fonte_recursos' => 'FR',
            'complemento_fonte' => 'CF',
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

    private function normalizeComplementoFonte(string|int|float|null $complementoFonte): string
    {
        if ($complementoFonte === null) {
            return '';
        }

        $valorNormalizado = trim((string) $complementoFonte);

        return $valorNormalizado;
    }

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }

    private function resolveClassePatrimonial(string $conta): ?int
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
