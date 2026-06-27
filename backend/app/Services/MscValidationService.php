<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MscTipo;
use App\Enums\MscUploadStatus;
use App\Enums\MscValidationErrorTipo;
use App\Models\MscUpload;
use App\Models\MscValidationError;
use App\Models\User;
use App\Services\Msc\Contracts\MscLineData;
use App\Services\Msc\MscLineValidator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class MscValidationService
{
    private const SICONFI_CODE_PATTERN = '/^\d{7}EX$/';

    private const CSV_DELIMITER = ';';

    private const BATCH_INSERT_SIZE = 500;

    private const CODIGO_REGRA_ESTRUTURA_SICONFI = 'ESTRUTURA_SICONFI';

    private const CODIGO_REGRA_QUANTIDADE_COLUNAS = 'QUANTIDADE_COLUNAS';

    private const CODIGO_REGRA_CABECALHO_INVALIDO = 'CABECALHO_INVALIDO';

    private const CODIGO_REGRA_FALHA_PROCESSAMENTO = 'FALHA_PROCESSAMENTO';

    private const COLUMN_CONTA = 0;

    private const COLUMN_VALOR = 13;

    private const COLUMN_TIPO_VALOR = 14;

    private const COLUMN_NATUREZA_VALOR = 15;

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

    public function __construct(
        private readonly MscLineValidator $lineValidator,
    ) {}

    private string $activeCsvDelimiter = self::CSV_DELIMITER;

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
     *     },
     *     errors: list<array{
     *         linha: int|null,
     *         codigo_regra: string,
     *         descricao: string,
     *         tipo: string
     *     }>
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
        $upload->load('validationErrors');

        return [
            'upload' => $this->formatUpload($upload),
            'errors' => $this->formatValidationErrors($upload),
        ];
    }

    private function processCsvStream(MscUpload $upload, string $path): void
    {
        $this->activeCsvDelimiter = $this->detectCsvDelimiter($path);

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            $this->recordProcessingFailure(
                $upload,
                'Não foi possível abrir o arquivo CSV para leitura.',
            );

            return;
        }

        try {
            $firstRow = fgetcsv($handle, 0, $this->activeCsvDelimiter);

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

            $headerRow = fgetcsv($handle, 0, $this->activeCsvDelimiter);

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

            $this->processDataRows($upload, $handle);
        } catch (Throwable $exception) {
            Log::error('Falha ao processar upload MSC.', [
                'upload_id' => $upload->id,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            $this->recordProcessingFailure(
                $upload,
                $this->resolveProcessingFailureMessage($exception),
            );
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param resource $handle
     */
    private function processDataRows(MscUpload $upload, $handle): void
    {
        /** @var list<array<string, int|string|null>> $errorsToInsert */
        $errorsToInsert = [];
        $hasValidationErrors = false;
        $lineNumber = 2;

        try {
            while (true) {
                $row = fgetcsv($handle, 0, $this->activeCsvDelimiter);

                if ($row === false) {
                    if (! feof($handle)) {
                        $this->recordProcessingFailure(
                            $upload,
                            sprintf(
                                'Erro ao ler a linha %d do arquivo CSV. Verifique delimitador, codificação (UTF-8) e formatação das aspas.',
                                $lineNumber + 1,
                            ),
                            $lineNumber + 1,
                        );
                    }

                    break;
                }

                $lineNumber++;

                if ($this->isEmptyDataRow($row)) {
                    continue;
                }

                $lineData = $this->parseLineData($lineNumber, $row);
                $lineErrors = $this->lineValidator->validateLine($lineData);

                if ($lineErrors === []) {
                    continue;
                }

                $hasValidationErrors = true;

                foreach ($lineErrors as $lineError) {
                    $errorsToInsert[] = $this->buildErrorRecord(
                        $upload,
                        $lineData->linha,
                        $lineData->conta,
                        $lineError['codigo_regra'],
                        $lineError['descricao'],
                    );

                    if (count($errorsToInsert) >= self::BATCH_INSERT_SIZE) {
                        $this->flushErrorsToInsert($errorsToInsert);
                    }
                }
            }

            if ($upload->status === MscUploadStatus::Falha) {
                return;
            }

            if ($hasValidationErrors) {
                $this->markUploadAsValidationError($upload);

                return;
            }

            $upload->update(['status' => MscUploadStatus::Sucesso]);
        } catch (Throwable $exception) {
            Log::error('Falha ao validar linhas da MSC.', [
                'upload_id' => $upload->id,
                'line' => $lineNumber,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            $this->recordProcessingFailure(
                $upload,
                $this->resolveProcessingFailureMessage($exception),
                $lineNumber > 2 ? $lineNumber : null,
            );
        } finally {
            $this->flushErrorsToInsert($errorsToInsert);
        }
    }

    private function detectCsvDelimiter(string $path): string
    {
        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return self::CSV_DELIMITER;
        }

        try {
            fgets($handle);
            $headerLine = fgets($handle);

            if ($headerLine === false) {
                return self::CSV_DELIMITER;
            }

            $expectedColumnCount = count(self::EXPECTED_HEADERS);
            $semicolonColumns = count(str_getcsv($headerLine, ';'));
            $commaColumns = count(str_getcsv($headerLine, ','));

            if ($semicolonColumns === $expectedColumnCount) {
                return ';';
            }

            if ($commaColumns === $expectedColumnCount) {
                return ',';
            }

            return $commaColumns > $semicolonColumns ? ',' : ';';
        } finally {
            fclose($handle);
        }
    }

    private function resolveProcessingFailureMessage(Throwable $exception): string
    {
        return sprintf(
            'Falha ao processar o arquivo: %s',
            $exception->getMessage(),
        );
    }

    private function recordProcessingFailure(
        MscUpload $upload,
        string $descricao,
        ?int $linha = null,
    ): void {
        $this->recordValidationError(
            $upload,
            $linha,
            self::CODIGO_REGRA_FALHA_PROCESSAMENTO,
            $descricao,
        );
        $upload->update(['status' => MscUploadStatus::Falha]);
    }

    /**
     * @param list<string|null> $row
     */
    private function isEmptyDataRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim($cell) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param list<string|null> $row
     */
    private function parseLineData(int $lineNumber, array $row): MscLineData
    {
        $columns = $this->normalizeCsvRow($row);

        return new MscLineData(
            linha: $lineNumber,
            conta: $columns[self::COLUMN_CONTA] ?? '',
            ics: $this->buildIcsFromRow($columns),
            valor: $this->parseValor($columns[self::COLUMN_VALOR] ?? ''),
            tipoValor: $columns[self::COLUMN_TIPO_VALOR] ?? '',
            naturezaValor: $columns[self::COLUMN_NATUREZA_VALOR] ?? '',
        );
    }

    /**
     * @param list<string> $columns
     *
     * @return array<string, string>
     */
    private function buildIcsFromRow(array $columns): array
    {
        $ics = [];

        for ($indice = 1; $indice <= 6; $indice++) {
            $icColumn = ($indice * 2) - 1;
            $tipoColumn = $indice * 2;

            $ics["IC{$indice}"] = $columns[$icColumn] ?? '';
            $ics["TIPO{$indice}"] = $columns[$tipoColumn] ?? '';
        }

        return $ics;
    }

    private function parseValor(string $rawValue): float
    {
        $value = trim($rawValue);

        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) $value;
    }

    /**
     * @return array<string, int|string|null>
     */
    private function buildErrorRecord(
        MscUpload $upload,
        int $linha,
        string $contaContabil,
        string $codigoRegra,
        string $descricao,
    ): array {
        $now = now();

        return [
            'id' => (string) Str::uuid(),
            'msc_upload_id' => $upload->id,
            'linha' => $linha,
            'conta_contabil' => $contaContabil !== '' ? $contaContabil : null,
            'tipo' => MscValidationErrorTipo::Erro->value,
            'codigo_regra' => $codigoRegra,
            'descricao' => $descricao,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param list<array<string, int|string|null>> $errorsToInsert
     */
    private function flushErrorsToInsert(array &$errorsToInsert): void
    {
        if ($errorsToInsert === []) {
            return;
        }

        MscValidationError::query()->insert($errorsToInsert);
        $errorsToInsert = [];
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
        ?int $linha,
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
     * @return list<array{
     *     linha: int|null,
     *     conta_contabil: string|null,
     *     codigo_regra: string,
     *     descricao: string,
     *     tipo: string
     * }>
     */
    private function formatValidationErrors(MscUpload $upload): array
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
