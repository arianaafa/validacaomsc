<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MscRuleValidationType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

final class MscRule extends Model
{
    use HasUuids;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'validation_type',
        'target_group',
        'objective',
        'error_message',
        'is_implemented',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'validation_type' => MscRuleValidationType::class,
            'is_implemented' => 'boolean',
        ];
    }
}
