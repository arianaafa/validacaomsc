<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Clients\SiconfiClient;
use App\Services\Msc\Contracts\MscFileFinalizerRuleInterface;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;
use RuntimeException;

final class D2_00001Rule implements MscFileFinalizerRuleInterface, MscStatefulRuleInterface
{
    private const CODE = 'D2_00001';

    private const ANEXO_DCA_I_HI = 'DCA-Anexo I-HI';

    private const COD_CONTA_VPA_FUNDEB = '4.5.2.2.0.00.00';

    private const MES_ENCERRAMENTO = 13;

    private const MENSAGEM_INCONSISTENCIA = 'não foi informado, no Anexo I-HI da DCA, valor da variação patrimonial aumentativa com o FUNDEB.';

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
            $items = $this->siconfiClient->getDcaAnexo($this->idEnte, $this->ano, self::ANEXO_DCA_I_HI);
        } catch (RuntimeException) {
            return self::MENSAGEM_INCONSISTENCIA;
        }

        if ($items === []) {
            return self::MENSAGEM_INCONSISTENCIA;
        }

        if ($this->possuiValorVpaFundebValido($items)) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    /**
     * @param list<array<string, bool|float|int|string|null>> $items
     */
    private function possuiValorVpaFundebValido(array $items): bool
    {
        foreach ($items as $item) {
            if (! $this->isContaVpaFundeb($item)) {
                continue;
            }

            $valor = $this->extrairValor($item);

            if ($valor !== null && $valor > 0.0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, bool|float|int|string|null> $item
     */
    private function isContaVpaFundeb(array $item): bool
    {
        $codConta = $item['cod_conta'] ?? null;

        if (is_string($codConta)) {
            $codContaNormalizado = ltrim($codConta, 'P');

            if ($codContaNormalizado === self::COD_CONTA_VPA_FUNDEB) {
                return true;
            }
        }

        $conta = $item['conta'] ?? null;

        if (is_string($conta) && str_starts_with($conta, self::COD_CONTA_VPA_FUNDEB)) {
            return true;
        }

        return false;
    }

    /**
     * @param array<string, bool|float|int|string|null> $item
     */
    private function extrairValor(array $item): ?float
    {
        $valor = $item['valor'] ?? null;

        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        if (is_string($valor) && is_numeric($valor)) {
            return (float) $valor;
        }

        return null;
    }
}
