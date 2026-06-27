<?php

declare(strict_types=1);

namespace App\Services\Msc;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;

final class MscLineValidator
{
    /**
     * @param list<MscRuleInterface> $rules
     */
    public function __construct(
        private readonly array $rules,
    ) {}

    /**
     * @return list<array{codigo_regra: string, descricao: string}>
     */
    public function validateLine(MscLineData $lineData): array
    {
        $errors = [];

        foreach ($this->rules as $rule) {
            $message = $rule->validate($lineData);

            if ($message === null) {
                continue;
            }

            $errors[] = [
                'codigo_regra' => $rule->getCode(),
                'descricao' => $message,
            ];
        }

        return $errors;
    }
}
