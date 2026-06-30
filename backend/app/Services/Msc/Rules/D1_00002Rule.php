<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscFileFinalizerRuleInterface;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;
use RuntimeException;

final class D1_00002Rule implements MscFileFinalizerRuleInterface, MscStatefulRuleInterface
{
    private const CODE = 'D1_00002';

    private const ENTREGAVEL_DCA = 'Declaração de Contas Anuais';

    private const STATUS_HOMOLOGADO = 'HO';

    private const MES_ENCERRAMENTO = 13;

    private const MENSAGEM_INCONSISTENCIA = 'ente federativo possui pendências na homologação da declaração de contas anuais (DCA) no Siconfi.';

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
        if ($this->mes !== self::MES_ENCERRAMENTO) {
            return null;
        }

        if ($this->idEnte === '' || $this->ano === 0) {
            return null;
        }

        try {
            $items = $this->siconfiClient->getExtratoEntregas($this->idEnte, $this->ano);
        } catch (RuntimeException) {
            return self::MENSAGEM_INCONSISTENCIA;
        }

        $registrosDca = $this->filtrarRegistrosDca($items);

        if ($this->dcaHomologada($registrosDca)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    /**
     * @param list<array<string, bool|float|int|string|null>> $items
     * @return list<array<string, bool|float|int|string|null>>
     */
    private function filtrarRegistrosDca(array $items): array
    {
        $registros = [];

        foreach ($items as $item) {
            $entregavel = $item['entregavel'] ?? null;

            if ($entregavel !== self::ENTREGAVEL_DCA) {
                continue;
            }

            $exercicio = $item['exercicio'] ?? null;

            if (is_int($exercicio) && $exercicio !== $this->ano) {
                continue;
            }

            $registros[] = $item;
        }

        return $registros;
    }

    /**
     * @param list<array<string, bool|float|int|string|null>> $registrosDca
     */
    private function dcaHomologada(array $registrosDca): bool
    {
        if ($registrosDca === []) {
            return false;
        }

        foreach ($registrosDca as $registro) {
            $statusRelatorio = $registro['status_relatorio'] ?? null;

            if ($statusRelatorio !== self::STATUS_HOMOLOGADO) {
                return false;
            }
        }

        return true;
    }
}
