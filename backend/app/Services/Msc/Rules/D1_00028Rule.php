<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscFileFinalizerRuleInterface;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;

final class D1_00028Rule implements MscFileFinalizerRuleInterface, MscStatefulRuleInterface
{
    private const CODE = 'D1_00028';

    private const MENSAGEM_INCONSISTENCIA = 'Não foram enviados valores diferentes de zero em todas as classes de contas (Patrimonial, orçamentária e controle) na MSC.';

    private bool $patrimonialComValor = false;

    private bool $orcamentariaComValor = false;

    private bool $controleComValor = false;

    public function getCode(): string
    {
        return self::CODE;
    }

    public function prepare(string $idEnte, int $ano, int $mes, string $tipoMatriz): void
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->patrimonialComValor = false;
        $this->orcamentariaComValor = false;
        $this->controleComValor = false;
    }

    /**
     * Acumula a presença de valores não zerados por dimensão de classe contábil.
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($lineData->valor == 0.0) {
            return null;
        }

        match ($this->resolveDimensaoClasse($lineData->conta)) {
            'patrimonial' => $this->patrimonialComValor = true,
            'orcamentaria' => $this->orcamentariaComValor = true,
            'controle' => $this->controleComValor = true,
            default => null,
        };

        return null;
    }

    public function finalizeFile(): ?string
    {
        if ($this->patrimonialComValor && $this->orcamentariaComValor && $this->controleComValor) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function resolveDimensaoClasse(string $conta): ?string
    {
        $contaNormalizada = $this->normalizeConta($conta);
        $primeiroDigito = $contaNormalizada[0] ?? '';

        if ($primeiroDigito === '' || ! ctype_digit($primeiroDigito)) {
            return null;
        }

        $digito = (int) $primeiroDigito;

        if (in_array($digito, [1, 2, 3, 4], true)) {
            return 'patrimonial';
        }

        if (in_array($digito, [5, 6], true)) {
            return 'orcamentaria';
        }

        if (in_array($digito, [7, 8], true)) {
            return 'controle';
        }

        return null;
    }

    private function normalizeConta(string $conta): string
    {
        return str_replace('.', '', trim($conta));
    }
}
