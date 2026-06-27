<?php

declare(strict_types=1);

namespace App\Services\Msc\Contracts;

final class MscLineData
{
    /**
     * @param array<string, string> $ics Mapa IC1/TIPO1 até IC6/TIPO6 extraído do CSV
     */
    public function __construct(
        public readonly int $linha,
        public readonly string $conta,
        public readonly array $ics,
        public readonly float $valor,
        public readonly string $tipoValor,
        public readonly string $naturezaValor,
    ) {}
}
