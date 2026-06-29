<?php

declare(strict_types=1);

namespace App\Http\Requests\Msc;

use App\Enums\MscRuleValidationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListRulesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string|Rule>>
     */
    public function rules(): array
    {
        return [
            'search' => [
                'sometimes',
                'nullable',
                'string',
                'max:255',
            ],
            'validation_type' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(MscRuleValidationType::class),
            ],
            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
            'per_page' => [
                'sometimes',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function filters(): array
    {
        $search = $this->string('search')->trim()->toString();

        return [
            'search' => $search !== '' ? $search : null,
            'validation_type' => $this->filled('validation_type')
                ? $this->string('validation_type')->toString()
                : null,
            'page' => (string) $this->integer('page', 1),
            'per_page' => (string) $this->integer('per_page', 15),
        ];
    }
}
