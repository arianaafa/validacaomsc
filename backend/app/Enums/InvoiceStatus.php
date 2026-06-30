<?php

declare(strict_types=1);

namespace App\Enums;

enum InvoiceStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Canceled = 'canceled';
}
