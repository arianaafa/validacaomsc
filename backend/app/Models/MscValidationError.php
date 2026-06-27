<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MscValidationErrorTipo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MscValidationError extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'msc_upload_id',
        'linha',
        'conta_contabil',
        'tipo',
        'codigo_regra',
        'descricao',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'linha' => 'integer',
            'tipo' => MscValidationErrorTipo::class,
        ];
    }

    public function mscUpload(): BelongsTo
    {
        return $this->belongsTo(MscUpload::class);
    }
}
