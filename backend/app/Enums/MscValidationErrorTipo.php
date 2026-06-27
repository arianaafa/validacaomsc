<?php

declare(strict_types=1);

namespace App\Enums;

enum MscValidationErrorTipo: string
{
    case Erro = 'erro';
    case Alerta = 'alerta';
}
