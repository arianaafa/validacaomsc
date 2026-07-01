<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

final class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'municipality_id',
        'is_superadmin',
        'force_password_change',
        'is_active',
        'is_trial',
        'trial_expires_at',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_superadmin' => 'boolean',
            'force_password_change' => 'boolean',
            'is_active' => 'boolean',
            'is_trial' => 'boolean',
            'trial_expires_at' => 'datetime',
        ];
    }

    public function isTrial(): bool
    {
        return $this->is_trial;
    }

    public function isTrialExpired(): bool
    {
        return $this->is_trial
            && $this->trial_expires_at !== null
            && $this->trial_expires_at->isPast();
    }

    /**
     * @return HasOne<LeadRequest, $this>
     */
    public function leadRequest(): HasOne
    {
        return $this->hasOne(LeadRequest::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->is_superadmin;
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * @return BelongsTo<Municipality, $this>
     */
    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    /**
     * @return HasMany<MscUpload, $this>
     */
    public function mscUploads(): HasMany
    {
        return $this->hasMany(MscUpload::class);
    }
}
