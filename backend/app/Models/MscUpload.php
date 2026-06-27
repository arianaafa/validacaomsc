<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MscTipo;
use App\Enums\MscUploadStatus;
use App\Helpers\IbgeHelper;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MscUpload extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'filename',
        'hash',
        'status',
        'periodo',
        'tipo_msc',
        'ibge_code',
        'total_lines',
        'total_errors',
        'total_alerts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MscUploadStatus::class,
            'tipo_msc' => MscTipo::class,
            'total_lines' => 'integer',
            'total_errors' => 'integer',
            'total_alerts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<MscValidationError, $this>
     */
    public function validationErrors(): HasMany
    {
        return $this->hasMany(MscValidationError::class);
    }

    /**
     * @return array{municipio: string, uf: string, estado: string}
     */
    public function getEnteAttribute(): array
    {
        if ($this->ibge_code === null || $this->ibge_code === '') {
            return [
                'municipio' => '',
                'uf' => '',
                'estado' => '',
            ];
        }

        return IbgeHelper::getMunicipioByCode($this->ibge_code);
    }
}
