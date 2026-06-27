<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MscUpload;
use App\Models\MscValidationError;

final class MscUploadFormatter
{
    /**
     * @return array{
     *     id: string,
     *     filename: string,
     *     hash: string,
     *     status: string,
     *     periodo: string,
     *     tipo_msc: string,
     *     ibge_code: string|null,
     *     ente: array{municipio: string, uf: string, estado: string},
     *     total_lines: int,
     *     total_errors: int,
     *     total_alerts: int,
     *     created_at: string|null
     * }
     */
    public static function format(MscUpload $upload): array
    {
        return [
            'id' => $upload->id,
            'filename' => $upload->filename,
            'hash' => $upload->hash,
            'status' => $upload->status->value,
            'periodo' => $upload->periodo,
            'tipo_msc' => $upload->tipo_msc->value,
            'ibge_code' => $upload->ibge_code,
            'ente' => $upload->ente,
            'total_lines' => (int) $upload->total_lines,
            'total_errors' => (int) $upload->total_errors,
            'total_alerts' => (int) $upload->total_alerts,
            'created_at' => $upload->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return list<array{
     *     linha: int|null,
     *     conta_contabil: string|null,
     *     codigo_regra: string,
     *     descricao: string,
     *     tipo: string
     * }>
     */
    public static function formatValidationErrors(MscUpload $upload): array
    {
        return $upload->validationErrors
            ->map(static fn (MscValidationError $error): array => [
                'linha' => $error->linha,
                'conta_contabil' => $error->conta_contabil,
                'codigo_regra' => $error->codigo_regra,
                'descricao' => $error->descricao,
                'tipo' => $error->tipo->value,
            ])
            ->values()
            ->all();
    }
}
