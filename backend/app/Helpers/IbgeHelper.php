<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

final class IbgeHelper
{
    private const API_BASE_URL = 'https://servicodados.ibge.gov.br/api/v1/localidades/municipios';

    private const CACHE_KEY_PREFIX = 'ibge:municipio:';

    private const HTTP_TIMEOUT_SECONDS = 10;

    /**
     * @return array{municipio: string, uf: string, estado: string}
     */
    public static function getMunicipioByCode(string $code): array
    {
        $normalizedCode = trim($code);

        if ($normalizedCode === '') {
            return self::emptyMunicipio();
        }

        try {
            return Cache::rememberForever(
                self::cacheKey($normalizedCode),
                static fn (): array => self::fetchMunicipioFromApi($normalizedCode),
            );
        } catch (Throwable) {
            return self::emptyMunicipio();
        }
    }

    /**
     * @return array{municipio: string, uf: string, estado: string}
     */
    private static function fetchMunicipioFromApi(string $code): array
    {
        $response = Http::timeout(self::HTTP_TIMEOUT_SECONDS)
            ->acceptJson()
            ->get(self::API_BASE_URL.'/'.$code);

        if (! $response->successful()) {
            throw new RuntimeException(sprintf(
                'IBGE API retornou status HTTP %d para o código %s.',
                $response->status(),
                $code,
            ));
        }

        /** @var mixed $payload */
        $payload = $response->json();

        if (! is_array($payload)) {
            throw new RuntimeException('Resposta inválida da API do IBGE.');
        }

        return self::parseApiResponse($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{municipio: string, uf: string, estado: string}
     */
    private static function parseApiResponse(array $data): array
    {
        $municipio = is_string($data['nome'] ?? null) ? $data['nome'] : '';

        $regiaoImediata = $data['regiao-imediata'] ?? null;
        $regiaoIntermediaria = is_array($regiaoImediata)
            ? ($regiaoImediata['regiao-intermediaria'] ?? null)
            : null;
        $uf = is_array($regiaoIntermediaria)
            ? ($regiaoIntermediaria['UF'] ?? null)
            : null;

        if (! is_array($uf)) {
            throw new RuntimeException('Estrutura de UF ausente na resposta da API do IBGE.');
        }

        $ufSigla = is_string($uf['sigla'] ?? null) ? $uf['sigla'] : '';
        $estado = is_string($uf['nome'] ?? null) ? $uf['nome'] : '';

        if ($municipio === '' || $ufSigla === '' || $estado === '') {
            throw new RuntimeException('Dados incompletos na resposta da API do IBGE.');
        }

        return [
            'municipio' => $municipio,
            'uf' => $ufSigla,
            'estado' => $estado,
        ];
    }

    /**
     * @return array{municipio: string, uf: string, estado: string}
     */
    private static function emptyMunicipio(): array
    {
        return [
            'municipio' => '',
            'uf' => '',
            'estado' => '',
        ];
    }

    private static function cacheKey(string $code): string
    {
        return self::CACHE_KEY_PREFIX.$code;
    }
}
