<?php

declare(strict_types=1);

namespace App\Enums;

enum MscUploadStatus: string
{
    case Processando = 'processando';
    case Sucesso = 'sucesso';
    case ErroValidacao = 'erro_validacao';
    case Falha = 'falha';
}
