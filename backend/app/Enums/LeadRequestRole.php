<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadRequestRole: string
{
    case Secretario = 'secretario';
    case Contador = 'contador';
    case Auditor = 'auditor';
    case Outros = 'outros';
}
