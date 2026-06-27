<?php

declare(strict_types=1);

namespace App\Services\Msc\Contracts;

interface MscStatefulRuleInterface extends MscRuleInterface
{
    public function prepare(string $idEnte, int $ano, int $mes, string $tipoMatriz): void;

    public function reset(): void;
}
