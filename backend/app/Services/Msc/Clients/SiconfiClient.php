<?php

declare(strict_types=1);

namespace App\Services\Msc\Clients;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

final class SiconfiClient
{
    private const BASE_URL = 'https://apidatalake.tesouro.gov.br/ords/cdwhprd/siconfi/tt/';

    private const ENDING_BALANCE = 'ending_balance';

    private const REQUEST_TIMEOUT_SECONDS = 30;

    private const EXTRATO_ENTREGAS_PAGE_SIZE = 500;

    /**
     * @return list<array<string, bool|float|int|string|null>>
     */
    public function getExtratoEntregas(string $idEnte, int $ano): array
    {
        $queryParams = [
            'id_ente' => (int) $idEnte,
            'an_referencia' => $ano,
        ];

        try {
            /** @var list<array<string, bool|float|int|string|null>> $items */
            $items = [];
            $offset = 0;

            do {
                $response = Http::baseUrl(self::BASE_URL)
                    ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                    ->acceptJson()
                    ->get('extrato_entregas', [
                        ...$queryParams,
                        'limit' => self::EXTRATO_ENTREGAS_PAGE_SIZE,
                        'offset' => $offset,
                    ])
                    ->throw();

                /** @var array{items?: list<array<string, bool|float|int|string|null>>, hasMore?: bool} $payload */
                $payload = $response->json();
                $pageItems = $payload['items'] ?? [];
                $items = [...$items, ...$pageItems];
                $hasMore = (bool) ($payload['hasMore'] ?? false);
                $offset += self::EXTRATO_ENTREGAS_PAGE_SIZE;
            } while ($hasMore);

            return $items;
        } catch (ConnectionException $exception) {
            Log::error('Timeout ou falha de conexão ao consultar extrato de entregas no Siconfi.', [
                'endpoint' => 'extrato_entregas',
                'query' => $queryParams,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Não foi possível consultar a API Siconfi: timeout ou falha de conexão.',
                0,
                $exception,
            );
        } catch (RequestException $exception) {
            Log::error('API Siconfi retornou erro HTTP ao consultar extrato de entregas.', [
                'endpoint' => 'extrato_entregas',
                'query' => $queryParams,
                'status' => $exception->response->status(),
                'body' => $exception->response->body(),
            ]);

            throw new RuntimeException(
                sprintf(
                    'A API Siconfi retornou erro HTTP %d ao consultar extrato de entregas.',
                    $exception->response->status(),
                ),
                0,
                $exception,
            );
        } catch (\Throwable $exception) {
            Log::error('Falha inesperada ao consultar extrato de entregas no Siconfi.', [
                'endpoint' => 'extrato_entregas',
                'query' => $queryParams,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw new RuntimeException(
                'Falha inesperada ao consultar extrato de entregas na API Siconfi.',
                0,
                $exception,
            );
        }
    }

    /**
     * @return list<array<string, bool|float|int|string|null>>
     */
    public function getMscMesAnterior(
        string $idEnte,
        int $ano,
        int $mes,
        string $tipoMatriz,
        int $classeConta,
    ): array {
        [$anoReferencia, $mesReferencia] = $this->resolveMesAnterior($ano, $mes);
        $endpoint = $this->resolveEndpoint($classeConta);

        $queryParams = [
            'id_ente' => (int) $idEnte,
            'an_referencia' => $anoReferencia,
            'me_referencia' => $mesReferencia,
            'co_tipo_matriz' => $tipoMatriz,
            'classe_conta' => $classeConta,
            'id_tv' => self::ENDING_BALANCE,
        ];

        try {
            $response = Http::baseUrl(self::BASE_URL)
                ->timeout(self::REQUEST_TIMEOUT_SECONDS)
                ->acceptJson()
                ->get($endpoint, $queryParams)
                ->throw();

            /** @var array{items?: list<array<string, bool|float|int|string|null>>} $payload */
            $payload = $response->json();

            return $payload['items'] ?? [];
        } catch (ConnectionException $exception) {
            Log::error('Timeout ou falha de conexão ao consultar API Siconfi.', [
                'endpoint' => $endpoint,
                'query' => $queryParams,
                'message' => $exception->getMessage(),
            ]);

            throw new RuntimeException(
                'Não foi possível consultar a API Siconfi: timeout ou falha de conexão.',
                0,
                $exception,
            );
        } catch (RequestException $exception) {
            Log::error('API Siconfi retornou erro HTTP.', [
                'endpoint' => $endpoint,
                'query' => $queryParams,
                'status' => $exception->response->status(),
                'body' => $exception->response->body(),
            ]);

            throw new RuntimeException(
                sprintf(
                    'A API Siconfi retornou erro HTTP %d ao consultar MSC do mês anterior.',
                    $exception->response->status(),
                ),
                0,
                $exception,
            );
        } catch (\Throwable $exception) {
            Log::error('Falha inesperada ao consultar API Siconfi.', [
                'endpoint' => $endpoint,
                'query' => $queryParams,
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            throw new RuntimeException(
                'Falha inesperada ao consultar a API Siconfi.',
                0,
                $exception,
            );
        }
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function resolveMesAnterior(int $ano, int $mes): array
    {
        if ($mes === 1) {
            return [$ano - 1, 12];
        }

        return [$ano, $mes - 1];
    }

    private function resolveEndpoint(int $classeConta): string
    {
        return match (true) {
            $classeConta >= 1 && $classeConta <= 4 => 'msc_patrimonial',
            $classeConta >= 5 && $classeConta <= 6 => 'msc_orcamentaria',
            $classeConta >= 7 && $classeConta <= 8 => 'msc_controle',
            default => throw new InvalidArgumentException(
                sprintf('Classe de conta inválida para consulta Siconfi: %d.', $classeConta),
            ),
        };
    }
}
