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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;
use ZipArchive;

final class MscValidationService
{
    private const SICONFI_CODE_PATTERN = '/^\d{7}EX$/';

    private const CSV_DELIMITER = ';';

    private const BATCH_INSERT_SIZE = 500;

    private const CODIGO_REGRA_ESTRUTURA_SICONFI = 'ESTRUTURA_SICONFI';

    private const CODIGO_REGRA_QUANTIDADE_COLUNAS = 'QUANTIDADE_COLUNAS';

    private const CODIGO_REGRA_CABECALHO_INVALIDO = 'CABECALHO_INVALIDO';

    private const CODIGO_REGRA_FALHA_PROCESSAMENTO = 'FALHA_PROCESSAMENTO';

    private const CODIGO_REGRA_MUNICIPIO_DIVERGENTE = 'MUNICIPIO_DIVERGENTE';

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
        private readonly \App\Services\Lead\LeadProvisioningService $leadProvisioningService,
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

        ['path' => $csvPath, 'tempDir' => $tempDir] = $this->resolveCsvPath(
            $path,
            $file->getClientOriginalName(),
        );

        try {
            $user->loadMissing('municipality');

            $this->leadProvisioningService->assertUserCanUpload($user);
            $user->refresh();

            $userIbgeCode = $user->municipality?->ibge_code;

            if ($userIbgeCode === null || $userIbgeCode === '') {
                throw ValidationException::withMessages([
                    'file' => ['Sua conta não está vinculada a um município. Entre em contato com o suporte.'],
                ]);
            }

            $spreadsheetIbgeCode = $this->extractIbgeCodeFromCsvPath($csvPath);

            $this->assertSpreadsheetMatchesUserMunicipality($user, $spreadsheetIbgeCode);

            $upload = DB::transaction(function () use ($user, $file, $periodo, $tipoMsc, $hash, $userIbgeCode): MscUpload {
                $existingUpload = $this->findExistingUpload($user, $periodo, $tipoMsc, $userIbgeCode);

                if ($existingUpload !== null) {
                    $this->handleExistingUpload($existingUpload, $userIbgeCode);
                }

                return MscUpload::query()->create([
                    'user_id' => $user->id,
                    'filename' => $file->getClientOriginalName(),
                    'hash' => $hash,
                    'status' => MscUploadStatus::Processando,
                    'periodo' => $periodo,
                    'tipo_msc' => $tipoMsc,
                    'ibge_code' => $userIbgeCode,
                ]);
            });

            $this->processCsvStream($upload, $csvPath);

            $upload->refresh();
            $upload->load('validationErrors');

            return [
                'upload' => MscUploadFormatter::format($upload),
                'errors' => MscUploadFormatter::formatValidationErrors($upload),
            ];
        } finally {
            $this->cleanupTempDirectory($tempDir);
        }
    }

    /**
     * @return array{path: string, tempDir: string|null}
     */
    private function resolveCsvPath(string $uploadPath, string $originalFilename): array
    {
        if (! $this->isZipUpload($uploadPath, $originalFilename)) {
            return [
                'path' => $uploadPath,
                'tempDir' => null,
            ];
        }

        return $this->extractCsvFromZip($uploadPath);
    }

    private function isZipUpload(string $path, string $originalFilename): bool
    {
        if (str_ends_with(strtolower($originalFilename), '.zip')) {
            return true;
        }

        $mimeType = mime_content_type($path);

        return is_string($mimeType) && in_array($mimeType, ['application/zip', 'application/x-zip-compressed'], true);
    }

    /**
     * @return array{path: string, tempDir: string}
     */
    private function extractCsvFromZip(string $zipPath): array
    {
        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            throw ValidationException::withMessages([
                'file' => ['Não foi possível abrir o arquivo ZIP enviado.'],
            ]);
        }

        try {
            $csvEntry = $this->findCsvEntryInZip($zip);
            $contents = $zip->getFromName($csvEntry);

            if ($contents === false) {
                throw ValidationException::withMessages([
                    'file' => ['Não foi possível extrair o CSV do arquivo ZIP.'],
                ]);
            }

            $tempDir = $this->createTempDirectory();
            $extractedPath = $tempDir.DIRECTORY_SEPARATOR.basename($csvEntry);

            if (file_put_contents($extractedPath, $contents) === false) {
                $this->cleanupTempDirectory($tempDir);

                throw ValidationException::withMessages([
                    'file' => ['Não foi possível preparar o CSV extraído do ZIP para validação.'],
                ]);
            }

            return [
                'path' => $extractedPath,
                'tempDir' => $tempDir,
            ];
        } finally {
            $zip->close();
        }
    }

    private function findCsvEntryInZip(ZipArchive $zip): string
    {
        /** @var list<string> $csvEntries */
        $csvEntries = [];

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $entryName = $zip->getNameIndex($index);

            if (! is_string($entryName) || str_ends_with($entryName, '/')) {
                continue;
            }

            if (preg_match('#^tmp/[^/]+\.csv$#i', $entryName) === 1) {
                $csvEntries[] = $entryName;
            }
        }

        if ($csvEntries === []) {
            throw ValidationException::withMessages([
                'file' => ['O arquivo ZIP não contém um CSV na pasta tmp/.'],
            ]);
        }

        if (count($csvEntries) > 1) {
            throw ValidationException::withMessages([
                'file' => ['O arquivo ZIP contém mais de um CSV na pasta tmp/. Envie um único arquivo.'],
            ]);
        }

        return $csvEntries[0];
    }

    private function createTempDirectory(): string
    {
        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'msc_upload_'.bin2hex(random_bytes(8));

        if (! mkdir($tempDir, 0700, true) && ! is_dir($tempDir)) {
            throw ValidationException::withMessages([
                'file' => ['Não foi possível preparar o diretório temporário para extrair o ZIP.'],
            ]);
        }

        return $tempDir;
    }

    private function cleanupTempDirectory(?string $tempDir): void
    {
        if ($tempDir === null || ! is_dir($tempDir)) {
            return;
        }

        File::deleteDirectory($tempDir);
    }

    private function findExistingUpload(
        User $user,
        string $periodo,
        MscTipo $tipoMsc,
        ?string $ibgeCode,
    ): ?MscUpload {
        $query = MscUpload::query()
            ->where('user_id', $user->id)
            ->where('periodo', $periodo)
            ->where('tipo_msc', $tipoMsc);

        if ($ibgeCode === null || $ibgeCode === '') {
            $query->whereNull('ibge_code');
        } else {
            $query->where('ibge_code', $ibgeCode);
        }

        return $query->first();
    }

    private function handleExistingUpload(MscUpload $existingUpload, ?string $ibgeCode): void
    {
        $enteLabel = $this->formatEnteLabelForMessage($existingUpload, $ibgeCode);

        if (
            $existingUpload->status === MscUploadStatus::Processando
            || $existingUpload->status === MscUploadStatus::Sucesso
        ) {
            $mensagem = match ($existingUpload->status) {
                MscUploadStatus::Processando => sprintf(
                    'Já existe um envio em processamento para o período %s (%s)%s. Aguarde a conclusão antes de enviar novamente.',
                    $existingUpload->periodo,
                    $existingUpload->tipo_msc->value,
                    $enteLabel,
                ),
                MscUploadStatus::Sucesso => sprintf(
                    'O período %s (%s)%s já está consolidado com sucesso. Não é permitido um novo envio para o mesmo ente.',
                    $existingUpload->periodo,
                    $existingUpload->tipo_msc->value,
                    $enteLabel,
                ),
            };

            throw ValidationException::withMessages([
                'periodo' => [$mensagem],
            ]);
        }

        if (
            $existingUpload->status === MscUploadStatus::ErroValidacao
            || $existingUpload->status === MscUploadStatus::Falha
        ) {
            $existingUpload->delete();

            return;
        }
    }

    private function formatEnteLabelForMessage(MscUpload $upload, ?string $ibgeCode): string
    {
        $code = $ibgeCode ?? $upload->ibge_code;

        if ($code === null || $code === '') {
            return '';
        }

        $ente = $upload->ente;
        $municipio = $ente['municipio'] ?? '';
        $uf = $ente['uf'] ?? '';

        if ($municipio !== '' && $uf !== '') {
            return sprintf(' para %s - %s (IBGE %s)', $municipio, $uf, $code);
        }

        return sprintf(' para o ente IBGE %s', $code);
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

            $idEnte = $this->extractIdEnteFromCodigo($this->extractSiconfiCode($firstRow) ?? '');

            if ($idEnte === null) {
                $this->recordValidationError(
                    $upload,
                    1,
                    self::CODIGO_REGRA_ESTRUTURA_SICONFI,
                    'Não foi possível extrair o código IBGE do ente na primeira linha do arquivo.',
                );
                $this->markUploadAsValidationError($upload);

                return;
            }

            if ($idEnte !== $upload->ibge_code) {
                $this->recordValidationError(
                    $upload,
                    1,
                    self::CODIGO_REGRA_MUNICIPIO_DIVERGENTE,
                    sprintf(
                        'O arquivo não pertence ao município cadastrado. O código IBGE da planilha (%s) difere do município vinculado à sua conta (IBGE %s).',
                        $idEnte,
                        $upload->ibge_code,
                    ),
                );
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

            [$anoReferencia, $mesReferencia] = $this->parsePeriodo($upload->periodo);
            $tipoMatriz = $this->resolveTipoMatriz($upload->tipo_msc);

            $this->lineValidator->prepareFileContext(
                $idEnte,
                $anoReferencia,
                $mesReferencia,
                $tipoMatriz,
            );

            try {
                $this->processDataRows($upload, $handle);
            } finally {
                $this->lineValidator->resetFileContext();
            }
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
     * @param  resource  $handle
     */
    private function processDataRows(MscUpload $upload, $handle): void
    {
        /** @var list<array<string, int|string|null>> $errorsToInsert */
        $errorsToInsert = [];
        $hasValidationErrors = false;
        $totalLines = 0;
        $lineNumber = 2;

        try {
            while (true) {
                $row = fgetcsv($handle, 0, $this->activeCsvDelimiter);

                if ($row === false) {
                    if (! feof($handle)) {
                        $this->flushErrorsToInsert($errorsToInsert);
                        $this->recordProcessingFailure(
                            $upload,
                            sprintf(
                                'Erro ao ler a linha %d do arquivo CSV. Verifique delimitador, codificação (UTF-8) e formatação das aspas.',
                                $lineNumber + 1,
                            ),
                            $lineNumber + 1,
                            $totalLines,
                        );
                    }

                    break;
                }

                $lineNumber++;

                if ($this->isEmptyDataRow($row)) {
                    continue;
                }

                $totalLines++;

                $lineData = $this->parseLineData($lineNumber, $row);
                $lineErrors = $this->lineValidator->validateLine($lineData);

                if ($lineErrors === []) {
                    continue;
                }

                $hasLineErrors = false;

                foreach ($lineErrors as $lineError) {
                    $errorsToInsert[] = $this->buildErrorRecord(
                        $upload,
                        $lineData->linha,
                        $lineData->conta,
                        $lineError['codigo_regra'],
                        $lineError['descricao'],
                        MscValidationErrorTipo::from($lineError['tipo']),
                    );

                    if ($lineError['tipo'] === MscValidationErrorTipo::Erro->value) {
                        $hasLineErrors = true;
                    }

                    if (count($errorsToInsert) >= self::BATCH_INSERT_SIZE) {
                        $this->flushErrorsToInsert($errorsToInsert);
                    }
                }

                if ($hasLineErrors) {
                    $hasValidationErrors = true;
                }
            }

            if ($upload->status === MscUploadStatus::Falha) {
                return;
            }

            foreach ($this->lineValidator->finalizeFile() as $fileError) {
                $errorsToInsert[] = $this->buildErrorRecord(
                    $upload,
                    0,
                    '',
                    $fileError['codigo_regra'],
                    $fileError['descricao'],
                    MscValidationErrorTipo::from($fileError['tipo']),
                );

                if ($fileError['tipo'] === MscValidationErrorTipo::Erro->value) {
                    $hasValidationErrors = true;
                }

                if (count($errorsToInsert) >= self::BATCH_INSERT_SIZE) {
                    $this->flushErrorsToInsert($errorsToInsert);
                }
            }

            if ($hasValidationErrors) {
                $this->flushErrorsToInsert($errorsToInsert);
                $this->markUploadAsValidationError($upload, $totalLines);

                return;
            }

            $this->flushErrorsToInsert($errorsToInsert);
            $this->finalizeUploadTotals($upload, $totalLines);
            $upload->update(['status' => MscUploadStatus::Sucesso]);
        } catch (Throwable $exception) {
            Log::error('Falha ao validar linhas da MSC.', [
                'upload_id' => $upload->id,
                'line' => $lineNumber,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            $this->flushErrorsToInsert($errorsToInsert);
            $this->recordProcessingFailure(
                $upload,
                $this->resolveProcessingFailureMessage($exception),
                $lineNumber > 2 ? $lineNumber : null,
                $totalLines,
            );
        } finally {
            $this->flushErrorsToInsert($errorsToInsert);
        }
    }

    private function finalizeUploadTotals(MscUpload $upload, int $totalLines): void
    {
        $counts = $upload->validationErrors()
            ->selectRaw('tipo, count(*) as aggregate')
            ->groupBy('tipo')
            ->pluck('aggregate', 'tipo');

        $upload->update([
            'total_lines' => $totalLines,
            'total_errors' => (int) ($counts[MscValidationErrorTipo::Erro->value] ?? 0),
            'total_alerts' => (int) ($counts[MscValidationErrorTipo::Alerta->value] ?? 0),
        ]);
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
        int $totalLines = 0,
    ): void {
        $this->recordValidationError(
            $upload,
            $linha,
            self::CODIGO_REGRA_FALHA_PROCESSAMENTO,
            $descricao,
        );
        $this->finalizeUploadTotals($upload, $totalLines);
        $upload->update(['status' => MscUploadStatus::Falha]);
    }

    /**
     * @param  list<string|null>  $row
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
     * @param  list<string|null>  $row
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
     * @param  list<string>  $columns
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
        MscValidationErrorTipo $tipo = MscValidationErrorTipo::Erro,
    ): array {
        $now = now();

        return [
            'id' => (string) Str::uuid(),
            'msc_upload_id' => $upload->id,
            'linha' => $linha,
            'conta_contabil' => $contaContabil !== '' ? $contaContabil : null,
            'tipo' => $tipo->value,
            'codigo_regra' => $codigoRegra,
            'descricao' => $descricao,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  list<array<string, int|string|null>>  $errorsToInsert
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
     * @param  list<string|null>  $row
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
     * @param  list<string|null>  $row
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
     * @param  list<string|null>  $row
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
     * @param  list<string|null>  $row
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
     * @param  list<string>  $columns
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
     * @param  list<array{position: int, expected: string, found: string}>  $invalidColumns
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

    private function markUploadAsValidationError(MscUpload $upload, int $totalLines = 0): void
    {
        $this->finalizeUploadTotals($upload, $totalLines);
        $upload->update(['status' => MscUploadStatus::ErroValidacao]);
    }

    private function extractIbgeCodeFromCsvPath(string $path): ?string
    {
        $delimiter = $this->detectCsvDelimiter($path);

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            $firstRow = fgetcsv($handle, 0, $delimiter);

            if ($firstRow === false) {
                return null;
            }

            return $this->extractIdEnteFromCodigo($this->extractSiconfiCode($firstRow) ?? '');
        } finally {
            fclose($handle);
        }
    }

    private function assertSpreadsheetMatchesUserMunicipality(User $user, ?string $spreadsheetIbgeCode): void
    {
        if ($spreadsheetIbgeCode === null || $spreadsheetIbgeCode === '') {
            return;
        }

        $userIbgeCode = $user->municipality?->ibge_code;

        if ($userIbgeCode === null || $userIbgeCode === '') {
            return;
        }

        if ($spreadsheetIbgeCode === $userIbgeCode) {
            return;
        }

        $municipalityName = $user->municipality->name ?? 'município cadastrado';

        throw ValidationException::withMessages([
            'file' => [sprintf(
                'O arquivo não pertence ao município cadastrado (%s). O código IBGE da planilha (%s) difere do município vinculado à sua conta (IBGE %s).',
                $municipalityName,
                $spreadsheetIbgeCode,
                $userIbgeCode,
            )],
        ]);
    }

    private function extractIdEnteFromCodigo(string $codigoSiconfi): ?string
    {
        if (preg_match('/^(\d{7})EX$/', $codigoSiconfi, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parsePeriodo(string $periodo): array
    {
        [$ano, $mes] = explode('-', $periodo);

        return [(int) $ano, (int) $mes];
    }

    private function resolveTipoMatriz(MscTipo $tipoMsc): string
    {
        return match ($tipoMsc) {
            MscTipo::Agregada => 'MSCC',
            MscTipo::Estendida => 'MSCE',
        };
    }

    private function removeUtf8Bom(string $value): string
    {
        if (str_starts_with($value, "\xEF\xBB\xBF")) {
            return substr($value, 3);
        }

        return $value;
    }
}
