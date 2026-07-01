<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\LeadRequestRole;
use App\Enums\LeadRequestStatus;
use App\Models\LeadRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeadRequest>
 */
class LeadRequestFactory extends Factory
{
    protected $model = LeadRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '83999999999',
            'organization_name' => fake()->city().' Prefeitura',
            'cnpj' => '12345678000199',
            'ibge_code' => '2507507',
            'role' => LeadRequestRole::Contador,
            'message' => null,
            'status' => LeadRequestStatus::Pending,
        ];
    }
}
