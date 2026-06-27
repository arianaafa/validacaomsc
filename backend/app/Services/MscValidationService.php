<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MscTipo;
use App\Enums\MscUploadStatus;
use App\Enums\MscValidationErrorTipo;
use App\Models\MscUpload;
use App\Models\MscValidationError;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

final class MscValidationService
{
    private const SICONFI_CODE_PATTERN = '/^\d{7}EX$/';

    private const CODIGO_REGRA_ESTRUTURA_SICONFI = 'ESTRUTURA_SICONFI';

    private const CODIGO_REGRA_QUANTIDADE_COLUNAS = 'QUANTIDADE_COLUNAS';

    private const CODIGO_REGRA_CABECALHO_INVALIDO = 'CABECALHO_INVALIDO';

    /**
     * @var list<string>
     */
    private const EXPECTED_HEADERS = [
        'CONTA',
        'IC1',
        'TIPO1',
        'IC2',
        'TIPO2',
        'IC3',
        'TIPO3',
        'IC4',
        'TIPO4',
        'IC5',
        'TIPO5',
        'IC6',
        'TIPO6',
        'Valor',
        'Tipo_valor',
        'Natureza_valor',
    ];

    /**
     * @return array{
     *     upload: array{
     *         id: string,
     *         filename: string,
     *         hash: string,
     *         status: string,
     *         periodo: string,
     *         tipo_msc: string,
     *         created_at: string|null
     *     }
     * }
     */
    public function processUpload(
        User $user,
        UploadedFile $file,
        string $periodo,
        MscTipo $tipoMsc,
    ): array {
        $path = $file->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => ['Não foi possível ler o arquivo enviado.'],
            ]);
        }

        $hash = hash_file('sha256', $path);

        if ($hash === false) {
            throw ValidationException::withMessages([
                'file' => ['Não foi possível calcular o hash do arquivo.'],
            ]);
        }

        if (MscUpload::query()->where('hash', $hash)->exists()) {
            throw ValidationException::withMessages([
                'file' => ['Este arquivo já foi enviado anteriormente.'],
            ]);
        }

        $upload = MscUpload::query()->create([
            'user_id' => $user->id,
            'filename' => $file->getClientOriginalName(),
            'hash' => $hash,
            'status' => MscUploadStatus::Processando,
            'periodo' => $periodo,
            'tipo_msc' => $tipoMsc,
        ]);

        $this->processCsvStream($upload, $path);

        $upload->refresh();

        return [
            'upload' => $this->formatUpload($upload),
        ];
    }

    private function processCsvStream(MscUpload $upload, string $path): void
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            $upload->update(['status' => MscUploadStatus::Falha]);

            return;
        }

        try {
            $firstRow = fgetcsv($handle);

            if ($firstRow === false) {
                $this->recordValidationError(
                    $upload,
                    1,
                    self::CODIGO_REGRA_ESTRUTURA_SICONFI,
                    'A primeira linha do arquivo está ausente ou vazia.',
                );
                $this->markUploadAsValidationError($upload);

                return;
            }

            if (! $this->validateSiconfiMetadata($upload, $firstRow, 1)) {
                $this->markUploadAsValidationError($upload);

                return;
            }

            $headerRow = fgetcsv($handle);

            if ($headerRow === false) {
                $this->recordValidationError(
                    $upload,
                    2,
                    self::CODIGO_REGRA_QUANTIDADE_COLUNAS,
                    sprintf(
                        'A linha de cabeçalho (linha 2) está ausente. Esperado exatamente %d colunas.',
                        count(self::EXPECTED_HEADERS),
                    ),
                );
                $this->markUploadAsValidationError($upload);

                return;
            }

            if (! $this->validateHeaderRow($upload, $headerRow, 2)) {
                $this->markUploadAsValidationError($upload);

                return;
            }

            while (fgetcsv($handle) !== false) {
                // Reservado para validações de conteúdo das linhas de dados.
            }

            if (feof($handle) === false) {
                $upload->update(['status' => MscUploadStatus::Falha]);

                return;
            }

            $upload->update(['status' => MscUploadStatus::Sucesso]);
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param list<string|null> $row
     */
    private function validateSiconfiMetadata(MscUpload $upload, array $row, int $lineNumber): bool
    {
        $codigoSiconfi = $this->extractSiconfiCode($row);

        if ($codigoSiconfi === null) {
            $this->recordValidationError(
                $upload,
                $lineNumber,
                self::CODIGO_REGRA_ESTRUTURA_SICONFI,
                'Código Siconfi não encontrado na primeira linha do arquivo.',
            );

            return false;
        }

        if (preg_match(self::SICONFI_CODE_PATTERN, $codigoSiconfi) !== 1) {
            $this->recordValidationError(
                $upload,
                $lineNumber,
                self::CODIGO_REGRA_ESTRUTURA_SICONFI,
                sprintf(
                    "Código Siconfi inválido '%s'. O padrão esperado é [7 dígitos IBGE]EX (ex.: 2507507EX).",
                    $codigoSiconfi,
                ),
            );

            return false;
        }

        return true;
    }

    /**
     * @param list<string|null> $row
     */
    private function validateHeaderRow(MscUpload $upload, array $row, int $lineNumber): bool
    {
        $columns = $this->normalizeCsvRow($row);
        $expectedColumnCount = count(self::EXPECTED_HEADERS);
        $columnCount = count($columns);

        if ($columnCount !== $expectedColumnCount) {
            $this->recordValidationError(
                $upload,
                $lineNumber,
                self::CODIGO_REGRA_QUANTIDADE_COLUNAS,
                sprintf(
                    'Quantidade de colunas inválida na linha de cabeçalho: encontrado %d, esperado %d.',
                    $columnCount,
                    $expectedColumnCount,
                ),
            );

            return false;
        }

        $invalidColumns = $this->findInvalidHeaderColumns($columns);

        if ($invalidColumns !== []) {
            $this->recordValidationError(
                $upload,
                $lineNumber,
                self::CODIGO_REGRA_CABECALHO_INVALIDO,
                $this->formatInvalidHeaderDescription($invalidColumns),
            );

            return false;
        }

        return true;
    }

    /**
     * @param list<string|null> $row
     */
    private function extractSiconfiCode(array $row): ?string
    {
        $firstNonEmpty = null;

        foreach ($row as $cell) {
            if ($cell === null) {
                continue;
            }

            $value = trim($this->removeUtf8Bom($cell));

            if ($value === '') {
                continue;
            }

            if ($firstNonEmpty === null) {
                $firstNonEmpty = $value;
            }

            if (preg_match(self::SICONFI_CODE_PATTERN, $value) === 1) {
                return $value;
            }
        }

        return $firstNonEmpty;
    }

    /**
     * @param list<string|null> $row
     *
     * @return list<string>
     */
    private function normalizeCsvRow(array $row): array
    {
        $normalized = [];

        foreach ($row as $index => $cell) {
            $value = $cell === null ? '' : trim($this->removeUtf8Bom($cell));
            $normalized[$index] = $value;
        }

        return $normalized;
    }

    /**
     * @param list<string> $columns
     *
     * @return list<array{position: int, expected: string, found: string}>
     */
    private function findInvalidHeaderColumns(array $columns): array
    {
        $invalidColumns = [];

        foreach (self::EXPECTED_HEADERS as $index => $expectedHeader) {
            $found = $columns[$index] ?? '';

            if (strcasecmp($found, $expectedHeader) !== 0) {
                $invalidColumns[] = [
                    'position' => $index + 1,
                    'expected' => $expectedHeader,
                    'found' => $found === '' ? '(vazio)' : $found,
                ];
            }
        }

        return $invalidColumns;
    }

    /**
     * @param list<array{position: int, expected: string, found: string}> $invalidColumns
     */
    private function formatInvalidHeaderDescription(array $invalidColumns): string
    {
        $details = array_map(
            static fn (array $column): string => sprintf(
                "coluna %d: esperado '%s', encontrado '%s'",
                $column['position'],
                $column['expected'],
                $column['found'],
            ),
            $invalidColumns,
        );

        return 'Cabeçalho inválido na linha guia. '.implode('; ', $details).'.';
    }

    private function recordValidationError(
        MscUpload $upload,
        int $linha,
        string $codigoRegra,
        string $descricao,
    ): void {
        MscValidationError::query()->create([
            'msc_upload_id' => $upload->id,
            'linha' => $linha,
            'conta_contabil' => null,
            'tipo' => MscValidationErrorTipo::Erro,
            'codigo_regra' => $codigoRegra,
            'descricao' => $descricao,
        ]);
    }

    private function markUploadAsValidationError(MscUpload $upload): void
    {
        $upload->update(['status' => MscUploadStatus::ErroValidacao]);
    }

    private function removeUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }

    /**
     * @return array{
     *     id: string,
     *     filename: string,
     *     hash: string,
     *     status: string,
     *     periodo: string,
     *     tipo_msc: string,
     *     created_at: string|null
     * }
     */
    private function formatUpload(MscUpload $upload): array
    {
        return [
            'id' => $upload->id,
            'filename' => $upload->filename,
            'hash' => $upload->hash,
            'status' => $upload->status->value,
            'periodo' => $upload->periodo,
            'tipo_msc' => $upload->tipo_msc->value,
            'created_at' => $upload->created_at?->toIso8601String(),
        ];
    }
}
