<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadRequestStatus: string
{
    case Pendente = 'pendente';
    case Contatado = 'contatado';
    case Concluido = 'concluido';
}
