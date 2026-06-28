<?php

declare(strict_types=1);

namespace App\Http\Requests\Msc;

use App\Enums\MscTipo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UploadRequest extends FormRequest
{
    private const MAX_FILE_SIZE_KB = 40960;

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
            'file' => [
                'required',
                'file',
                'mimes:csv,txt,zip',
                'max:'.self::MAX_FILE_SIZE_KB,
            ],
            'periodo' => [
                'required',
                'string',
                'regex:/^\d{4}-(0[1-9]|1[0-2])$/',
            ],
            'tipo_msc' => [
                'required',
                'string',
                Rule::enum(MscTipo::class),
            ],
        ];
    }
}
