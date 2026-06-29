<?php

declare(strict_types=1);

namespace App\Enums;

enum MscRuleValidationType: string
{
    case Linha = 'linha';
    case Global = 'global';
    case Agrupamento = 'agrupamento';
}
