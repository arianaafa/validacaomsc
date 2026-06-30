<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscFileFinalizerRuleInterface;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;
use DateTimeImmutable;
use Exception;
use RuntimeException;

final class D1_00006Rule implements MscFileFinalizerRuleInterface, MscStatefulRuleInterface
{
    private const CODE = 'D1_00006';

    private const ENTREGAVEL_RREO = 'Relatório Resumido de Execução Orçamentária';

    private const PERIODICIDADE_BIMESTRAL = 'B';

    private const STATUS_HOMOLOGADO = 'HO';

    private const MENSAGEM_INCONSISTENCIA = 'ente federativo enviou ou homologou o relatório resumido de execução orçamentária (RREO) fora do prazo legal previsto na LRF.';

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

        try {
            $items = $this->siconfiClient->getExtratoEntregas($this->idEnte, $this->ano);
        } catch (RuntimeException) {
            return self::MENSAGEM_INCONSISTENCIA;
        }

        $registrosRreoHomologados = $this->filtrarRegistrosRreoHomologados($items);

        if ($registrosRreoHomologados === []) {
            return null;
        }

        if ($this->todosHomologadosDentroDoPrazo($registrosRreoHomologados)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    /**
     * @param list<array<string, bool|float|int|string|null>> $items
     * @return list<array<string, bool|float|int|string|null>>
     */
    private function filtrarRegistrosRreoHomologados(array $items): array
    {
        $registros = [];

        foreach ($items as $item) {
            $entregavel = $item['entregavel'] ?? null;
            $periodicidade = $item['periodicidade'] ?? null;
            $statusRelatorio = $item['status_relatorio'] ?? null;

            if ($entregavel !== self::ENTREGAVEL_RREO) {
                continue;
            }

            if ($periodicidade !== self::PERIODICIDADE_BIMESTRAL) {
                continue;
            }

            if ($statusRelatorio !== self::STATUS_HOMOLOGADO) {
                continue;
            }

            $registros[] = $item;
        }

        return $registros;
    }

    /**
     * @param list<array<string, bool|float|int|string|null>> $registrosRreoHomologados
     */
    private function todosHomologadosDentroDoPrazo(array $registrosRreoHomologados): bool
    {
        foreach ($registrosRreoHomologados as $registro) {
            $periodo = (int) ($registro['periodo'] ?? 0);
            $dataLimite = $this->resolveDataLimite($periodo, $this->ano);

            if ($dataLimite === null) {
                continue;
            }

            $dataStatus = $registro['data_status'] ?? null;

            if (! is_string($dataStatus)) {
                return false;
            }

            $dataHomologacao = $this->parseDataStatus($dataStatus);

            if ($dataHomologacao === null) {
                return false;
            }

            if ($dataHomologacao > $dataLimite) {
                return false;
            }
        }

        return true;
    }

    private function resolveDataLimite(int $periodo, int $anoReferencia): ?DateTimeImmutable
    {
        return match ($periodo) {
            1 => new DateTimeImmutable(sprintf('%d-03-30', $anoReferencia)),
            2 => new DateTimeImmutable(sprintf('%d-05-30', $anoReferencia)),
            3 => new DateTimeImmutable(sprintf('%d-07-30', $anoReferencia)),
            4 => new DateTimeImmutable(sprintf('%d-09-30', $anoReferencia)),
            5 => new DateTimeImmutable(sprintf('%d-11-30', $anoReferencia)),
            6 => new DateTimeImmutable(sprintf('%d-01-30', $anoReferencia + 1)),
            default => null,
        };
    }

    private function parseDataStatus(string $dataStatus): ?DateTimeImmutable
    {
        try {
            $dataHomologacao = new DateTimeImmutable($dataStatus);

            return $dataHomologacao->setTime(0, 0);
        } catch (Exception) {
            return null;
        }
    }
}
