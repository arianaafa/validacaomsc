<?php

declare(strict_types=1);

namespace App\Services\Msc;

use App\Enums\MscRuleValidationType;
use App\Models\MscRule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class MscRuleService
{
    private const DEFAULT_PER_PAGE = 15;

    /**
     * @param array<string, string|null> $filters
     */
    public function listRules(array $filters): LengthAwarePaginator
    {
        $query = MscRule::query()->orderBy('code');

        $search = $filters['search'] ?? null;

        if ($search !== null) {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('code', 'ilike', '%'.$search.'%')
                    ->orWhere('name', 'ilike', '%'.$search.'%')
                    ->orWhere('objective', 'ilike', '%'.$search.'%');
            });
        }

        $validationType = $filters['validation_type'] ?? null;

        if ($validationType !== null) {
            $query->where(
                'validation_type',
                MscRuleValidationType::from($validationType),
            );
        }

        $page = max(1, (int) ($filters['page'] ?? 1));
        $perPage = max(1, min(100, (int) ($filters['per_page'] ?? self::DEFAULT_PER_PAGE)));

        return $query->paginate(perPage: $perPage, page: $page);
    }
}
