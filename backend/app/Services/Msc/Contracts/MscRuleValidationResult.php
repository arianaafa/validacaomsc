<?php

declare(strict_types=1);

namespace App\Services\Msc\Contracts;

use App\Enums\MscValidationErrorTipo;

final class MscRuleValidationResult
{
    public function __construct(
        public readonly string $codigoRegra,
        public readonly string $descricao,
        public readonly MscValidationErrorTipo $tipo,
    ) {}
}
