<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;

final class D1_00036Rule implements MscStatefulRuleInterface
{
    private const CODE = 'D1_00036';

    private const TIPO_SALDO_FINAL = 'ending_balance';

    private const MES_ENCERRAMENTO = 13;

    private const MENSAGEM_INCONSISTENCIA = 'contas de VPA e VPD com saldo final na MSC de encerramento.';

    /**
     * Classes de resultado (VPD e VPA) no PCASP Estendido.
     *
     * @var list<string>
     */
    private const PREFIXOS_CLASSE_RESULTADO = ['3', '4'];

    private int $mes = 0;

    public function getCode(): string
    {
        return self::CODE;
    }

    public function prepare(string $idEnte, int $ano, int $mes, string $tipoMatriz): void
    {
        $this->reset();
        $this->mes = $mes;
    }

    public function reset(): void
    {
        $this->mes = 0;
    }

    /**
     * Na MSC de encerramento, contas VPD (3) e VPA (4) devem apresentar saldo final zerado.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($this->mes !== self::MES_ENCERRAMENTO) {
            return null;
        }

        if ($this->normalizeTipoValor($lineData->tipoValor) !== self::TIPO_SALDO_FINAL) {
            return null;
        }

        if (! $this->isContaClasseResultado($lineData->conta)) {
            return null;
        }

        if ($lineData->valor == 0.0) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isContaClasseResultado(string $conta): bool
    {
        $contaNormalizada = $this->normalizeConta($conta);
        $primeiroDigito = $contaNormalizada[0] ?? '';

        return in_array($primeiroDigito, self::PREFIXOS_CLASSE_RESULTADO, true);
    }

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }

    private function normalizeTipoValor(string $tipoValor): string
    {
        return strtolower(trim($tipoValor));
    }
}
