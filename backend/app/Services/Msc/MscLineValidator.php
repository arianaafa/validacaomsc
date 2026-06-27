<?php

declare(strict_types=1);

namespace App\Services\Msc;

use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\Contracts\MscRuleInterface;
use App\Services\Msc\Contracts\MscRuleResultInterface;
use App\Services\Msc\Contracts\MscStatefulRuleInterface;

final class MscLineValidator
{
    /**
     * @param list<MscRuleInterface> $rules
     */
    public function __construct(
        private readonly array $rules,
    ) {}

    public function prepareFileContext(string $idEnte, int $ano, int $mes, string $tipoMatriz): void
    {
        foreach ($this->rules as $rule) {
            if (! $rule instanceof MscStatefulRuleInterface) {
                continue;
            }

            $rule->prepare($idEnte, $ano, $mes, $tipoMatriz);
        }
    }

    public function resetFileContext(): void
    {
        foreach ($this->rules as $rule) {
            if (! $rule instanceof MscStatefulRuleInterface) {
                continue;
            }

            $rule->reset();
        }
    }

    /**
     * @return list<array{codigo_regra: string, descricao: string, tipo: string}>
     */
    public function validateLine(MscLineData $lineData): array
    {
        $errors = [];

        foreach ($this->rules as $rule) {
            if ($rule instanceof MscRuleResultInterface) {
                $result = $rule->validateResult($lineData);

                if ($result === null) {
                    continue;
                }

                $errors[] = [
                    'codigo_regra' => $result->codigoRegra,
                    'descricao' => $result->descricao,
                    'tipo' => $result->tipo->value,
                ];

                continue;
            }

            $message = $rule->validate($lineData);

            if ($message === null) {
                continue;
            }

            $errors[] = [
                'codigo_regra' => $rule->getCode(),
                'descricao' => $message,
                'tipo' => 'erro',
            ];
        }

        return $errors;
    }
}
