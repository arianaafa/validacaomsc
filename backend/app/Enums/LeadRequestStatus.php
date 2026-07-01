<?php

declare(strict_types=1);

namespace App\Enums;

enum LeadRequestStatus: string
{
    case Pending = 'pending';
    case Trial = 'trial';
    case Approved = 'approved';
    case Failed = 'failed';
}
