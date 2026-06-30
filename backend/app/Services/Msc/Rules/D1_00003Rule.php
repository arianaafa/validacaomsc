<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscFileFinalizerRuleInterface;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;
use RuntimeException;

final class D1_00003Rule implements MscFileFinalizerRuleInterface, MscStatefulRuleInterface
{
    private const CODE = 'D1_00003';

    private const ENTREGAVEL_RGF = 'Relatório de Gestão Fiscal';

    private const PERIODICIDADE_QUADRIMESTRAL = 'Q';

    private const STATUS_HOMOLOGADO = 'HO';

    private const MES_ENCERRAMENTO = 13;

    private const MAXIMO_QUADRIMESTRES = 3;

    private const MENSAGEM_INCONSISTENCIA = 'poder executivo do ente federativo possui pendências na homologação de relatórios de gestão fiscal (RGF) no Siconfi.';

    /**
     * @var list<string>
     */
    private const PADROES_INSTITUICAO_PODER_EXECUTIVO = [
        'Prefeitura',
        'Governo do Estado',
        'Governo do Distrito Federal',
    ];

    private string $idEnte = '';

    private int $ano = 0;

    private int $mes = 0;

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
    }

    public function reset(): void
    {
        $this->idEnte = '';
        $this->ano = 0;
        $this->mes = 0;
    }

    public function validate(MscLineData $lineData): ?string
    {
        return null;
    }

    public function finalizeFile(): ?string
    {
        if ($this->idEnte === '' || $this->ano === 0 || $this->mes === 0) {
            return null;
        }

        $periodosObrigatorios = $this->resolvePeriodosObrigatorios($this->mes);

        if ($periodosObrigatorios === []) {
            return null;
        }

        try {
            $items = $this->siconfiClient->getExtratoEntregas($this->idEnte, $this->ano);
        } catch (RuntimeException) {
            return self::MENSAGEM_INCONSISTENCIA;
        }

        $registrosRgfExecutivo = $this->filtrarRegistrosRgfExecutivo($items);

        if ($this->todosPeriodosHomologados($periodosObrigatorios, $registrosRgfExecutivo)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    /**
     * @return list<int>
     */
    private function resolvePeriodosObrigatorios(int $mes): array
    {
        if ($mes <= 4) {
            return [];
        }

        if ($mes === self::MES_ENCERRAMENTO) {
            return range(1, self::MAXIMO_QUADRIMESTRES);
        }

        if ($mes <= 8) {
            return [1];
        }

        return [1, 2];
    }

    /**
     * @param list<array<string, bool|float|int|string|null>> $items
     * @return list<array<string, bool|float|int|string|null>>
     */
    private function filtrarRegistrosRgfExecutivo(array $items): array
    {
        $registros = [];

        foreach ($items as $item) {
            $entregavel = $item['entregavel'] ?? null;
            $periodicidade = $item['periodicidade'] ?? null;
            $instituicao = $item['instituicao'] ?? null;

            if ($entregavel !== self::ENTREGAVEL_RGF) {
                continue;
            }

            if ($periodicidade !== self::PERIODICIDADE_QUADRIMESTRAL) {
                continue;
            }

            if (! is_string($instituicao) || ! $this->isInstituicaoPoderExecutivo($instituicao)) {
                continue;
            }

            $registros[] = $item;
        }

        return $registros;
    }

    private function isInstituicaoPoderExecutivo(string $instituicao): bool
    {
        foreach (self::PADROES_INSTITUICAO_PODER_EXECUTIVO as $padrao) {
            if (stripos($instituicao, $padrao) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<int> $periodosObrigatorios
     * @param list<array<string, bool|float|int|string|null>> $registrosRgfExecutivo
     */
    private function todosPeriodosHomologados(array $periodosObrigatorios, array $registrosRgfExecutivo): bool
    {
        foreach ($periodosObrigatorios as $periodoObrigatorio) {
            $registrosDoPeriodo = array_values(array_filter(
                $registrosRgfExecutivo,
                static fn (array $registro): bool => (int) ($registro['periodo'] ?? 0) === $periodoObrigatorio,
            ));

            if ($registrosDoPeriodo === []) {
                return false;
            }

            foreach ($registrosDoPeriodo as $registro) {
                $statusRelatorio = $registro['status_relatorio'] ?? null;

                if ($statusRelatorio !== self::STATUS_HOMOLOGADO) {
                    return false;
                }
            }
        }

        return true;
    }
}
