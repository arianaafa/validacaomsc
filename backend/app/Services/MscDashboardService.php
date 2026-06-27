<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MscUpload;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class MscDashboardService
{
    /**
     * @return array{
     *     summary: array{
     *         total_competencias: int,
     *         media_inconsistencias_mes: float,
     *         taxa_conformidade: float
     *     },
     *     trend: list<array{
     *         periodo: string,
     *         total_errors: int,
     *         total_alerts: int
     *     }>,
     *     uploads: list<array{
     *         id: string,
     *         filename: string,
     *         hash: string,
     *         status: string,
     *         periodo: string,
     *         tipo_msc: string,
     *         total_lines: int,
     *         total_errors: int,
     *         total_alerts: int,
     *         created_at: string|null
     *     }>
     * }
     */
    public function getDashboardForUser(User $user): array
    {
        /** @var Collection<int, MscUpload> $uploads */
        $uploads = MscUpload::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['sucesso', 'erro_validacao', 'falha'])
            ->orderByDesc('periodo')
            ->orderByDesc('created_at')
            ->get();

        return [
            'summary' => $this->buildSummary($uploads),
            'trend' => $this->buildTrend($uploads),
            'uploads' => $uploads
                ->map(static fn (MscUpload $upload): array => MscUploadFormatter::format($upload))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param Collection<int, MscUpload> $uploads
     *
     * @return array{
     *     total_competencias: int,
     *     media_inconsistencias_mes: float,
     *     taxa_conformidade: float
     * }
     */
    private function buildSummary(Collection $uploads): array
    {
        $totalCompetencias = $uploads->count();

        if ($totalCompetencias === 0) {
            return [
                'total_competencias' => 0,
                'media_inconsistencias_mes' => 0.0,
                'taxa_conformidade' => 100.0,
            ];
        }

        $monthlyTotals = $uploads
            ->groupBy(static fn (MscUpload $upload): string => $upload->periodo)
            ->map(static fn (Collection $group): float => $group->avg(
                static fn (MscUpload $upload): float => (float) ($upload->total_errors + $upload->total_alerts),
            ) ?? 0.0);

        $mediaInconsistenciasMes = round((float) $monthlyTotals->avg(), 1);

        $totalLines = (int) $uploads->sum('total_lines');
        $totalErrors = (int) $uploads->sum('total_errors');

        $taxaConformidade = $totalLines > 0
            ? round(max(0.0, (1 - ($totalErrors / $totalLines)) * 100), 1)
            : 100.0;

        return [
            'total_competencias' => $totalCompetencias,
            'media_inconsistencias_mes' => $mediaInconsistenciasMes,
            'taxa_conformidade' => $taxaConformidade,
        ];
    }

    /**
     * @param Collection<int, MscUpload> $uploads
     *
     * @return list<array{
     *     periodo: string,
     *     total_errors: int,
     *     total_alerts: int
     * }>
     */
    private function buildTrend(Collection $uploads): array
    {
        return $uploads
            ->groupBy(static fn (MscUpload $upload): string => $upload->periodo)
            ->map(static fn (Collection $group, string $periodo): array => [
                'periodo' => $periodo,
                'total_errors' => (int) $group->sum('total_errors'),
                'total_alerts' => (int) $group->sum('total_alerts'),
            ])
            ->sortBy('periodo')
            ->values()
            ->all();
    }
}
