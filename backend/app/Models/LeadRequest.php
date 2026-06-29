<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadRequestRole;
use App\Enums\LeadRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class LeadRequest extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'organization_name',
        'role',
        'message',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => LeadRequestRole::class,
            'status' => LeadRequestStatus::class,
        ];
    }
}
