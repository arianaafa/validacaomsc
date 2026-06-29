<?php

declare(strict_types=1);

namespace App\Services\Msc\Rules;

use App\Services\Msc\Contracts\MscFileFinalizerRuleInterface;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;

final class D1_00037Rule implements MscFileFinalizerRuleInterface, MscStatefulRuleInterface
{
    private const CODE = 'D1_00037';

    private const IC_FONTE_RECURSOS = 'FR';

    private const TAMANHO_FONTE_RECURSOS = 4;

    private const FAIXA_UNIAO_MINIMA = 0;

    private const FAIXA_UNIAO_MAXIMA = 499;

    private const MENSAGEM_INCONSISTENCIA = 'Não foram enviadas informações em fontes de recursos da União (de 000 a 499) na MSC.';

    private bool $fonteUniaoComValor = false;

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
        $this->fonteUniaoComValor = false;
    }

    /**
     * Acumula a presença de valores não zerados em fontes de recursos da União (000 a 499).
     */
    public function validate(MscLineData $lineData): ?string
    {
        if ($lineData->valor == 0.0) {
            return null;
        }

        $icMap = $this->extractIcMap($lineData->ics);
        $fonteRecursos = trim($icMap[self::IC_FONTE_RECURSOS] ?? '');

        if ($fonteRecursos === '' || ! $this->isFonteRecursosUniao($fonteRecursos)) {
            return null;
        }

        $this->fonteUniaoComValor = true;

        return null;
    }

    public function finalizeFile(): ?string
    {
        if ($this->fonteUniaoComValor) {
            return null;
        }

        return self::MENSAGEM_INCONSISTENCIA;
    }

    private function isFonteRecursosUniao(string $fonteRecursos): bool
    {
        if (! ctype_digit($fonteRecursos)) {
            return false;
        }

        $fonteNormalizada = str_pad($fonteRecursos, self::TAMANHO_FONTE_RECURSOS, '0', STR_PAD_LEFT);

        if (strlen($fonteNormalizada) !== self::TAMANHO_FONTE_RECURSOS) {
            return false;
        }

        $codigoSemPrimeiroDigito = substr($fonteNormalizada, 1, 3);
        $valor = (int) $codigoSemPrimeiroDigito;

        return $valor >= self::FAIXA_UNIAO_MINIMA && $valor <= self::FAIXA_UNIAO_MAXIMA;
    }

    /**
     * @param array<string, string> $ics
     * @return array<string, string>
     */
    private function extractIcMap(array $ics): array
    {
        $icMap = [];

        for ($indice = 1; $indice <= 6; $indice++) {
            $valorIc = trim($ics["IC{$indice}"] ?? '');
            $tipoIc = strtoupper(trim($ics["TIPO{$indice}"] ?? ''));

            if ($valorIc === '' || $tipoIc === '') {
                continue;
            }

            $icMap[$tipoIc] = $valorIc;
        }

        return $icMap;
    }
}
