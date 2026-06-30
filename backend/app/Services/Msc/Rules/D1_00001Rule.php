<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscFileFinalizerRuleInterface;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;
use RuntimeException;

final class D1_00001Rule implements MscFileFinalizerRuleInterface, MscStatefulRuleInterface
{
    private const CODE = 'D1_00001';

    private const ENTREGAVEL_RREO = 'Relatório Resumido de Execução Orçamentária';

    private const PERIODICIDADE_BIMESTRAL = 'B';

    private const STATUS_HOMOLOGADO = 'HO';

    private const MAXIMO_BIMESTRES = 6;

    private const MENSAGEM_INCONSISTENCIA = 'ente federativo possui pendências na homologação de relatórios resumidos de execução orçamentária (RREO) no Siconfi.';

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

        $registrosRreo = $this->filtrarRegistrosRreo($items);

        if ($this->todosPeriodosHomologados($periodosObrigatorios, $registrosRreo)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    /**
     * @return list<int>
     */
    private function resolvePeriodosObrigatorios(int $mes): array
    {
        if ($mes <= 1) {
            return [];
        }

        $quantidadeBimestres = (int) floor(($mes - 2) / 2) + 1;
        $quantidadeBimestres = min($quantidadeBimestres, self::MAXIMO_BIMESTRES);

        return range(1, $quantidadeBimestres);
    }

    /**
     * @param list<array<string, bool|float|int|string|null>> $items
     * @return list<array<string, bool|float|int|string|null>>
     */
    private function filtrarRegistrosRreo(array $items): array
    {
        $registros = [];

        foreach ($items as $item) {
            $entregavel = $item['entregavel'] ?? null;
            $periodicidade = $item['periodicidade'] ?? null;

            if ($entregavel !== self::ENTREGAVEL_RREO) {
                continue;
            }

            if ($periodicidade !== self::PERIODICIDADE_BIMESTRAL) {
                continue;
            }

            $registros[] = $item;
        }

        return $registros;
    }

    /**
     * @param list<int> $periodosObrigatorios
     * @param list<array<string, bool|float|int|string|null>> $registrosRreo
     */
    private function todosPeriodosHomologados(array $periodosObrigatorios, array $registrosRreo): bool
    {
        foreach ($periodosObrigatorios as $periodoObrigatorio) {
            $registrosDoPeriodo = array_values(array_filter(
                $registrosRreo,
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
