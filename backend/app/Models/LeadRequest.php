<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LeadRequestRole;
use App\Enums\LeadRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class LeadRequest extends Model
{
    /** @use HasFactory<LeadRequestFactory> */
    use HasFactory, HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'organization_name',
        'cnpj',
        'ibge_code',
        'role',
        'message',
        'status',
        'user_id',
        'trial_started_at',
        'trial_expires_at',
        'approved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => LeadRequestRole::class,
            'status' => LeadRequestStatus::class,
            'trial_started_at' => 'datetime',
            'trial_expires_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
