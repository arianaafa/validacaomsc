<?php

declare(strict_types=1);

namespace App\Http\Requests\Msc;

use App\Enums\LeadRequestRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreLeadRequest extends FormRequest
{
    private const MAX_NAME_LENGTH = 255;

    private const MAX_EMAIL_LENGTH = 255;

    private const MAX_PHONE_LENGTH = 20;

    private const MAX_ORGANIZATION_LENGTH = 255;

    private const MAX_MESSAGE_LENGTH = 2000;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:'.self::MAX_NAME_LENGTH,
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:'.self::MAX_EMAIL_LENGTH,
            ],
            'phone' => [
                'required',
                'string',
                'max:'.self::MAX_PHONE_LENGTH,
                'regex:/^[\d\s()+\-]+$/',
            ],
            'organization_name' => [
                'required',
                'string',
                'max:'.self::MAX_ORGANIZATION_LENGTH,
            ],
            'role' => [
                'required',
                'string',
                Rule::enum(LeadRequestRole::class),
            ],
            'message' => [
                'sometimes',
                'nullable',
                'string',
                'max:'.self::MAX_MESSAGE_LENGTH,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function payload(): array
    {
        $message = $this->string('message')->trim()->toString();

        return [
            'name' => $this->string('name')->trim()->toString(),
            'email' => $this->string('email')->trim()->toString(),
            'phone' => $this->string('phone')->trim()->toString(),
            'organization_name' => $this->string('organization_name')->trim()->toString(),
            'role' => $this->string('role')->toString(),
            'message' => $message !== '' ? $message : null,
        ];
    }
}
