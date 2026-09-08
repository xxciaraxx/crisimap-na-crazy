<?php

namespace App\Services;

use App\Models\GeocodeCache;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class NominatimGeocodingService
{
    public function __construct(
        private readonly NominatimThrottle $throttle,
    ) {
    }

    public function geocodeAddress(string $query): ?array
    {
        $results = $this->search($query, 1);
        $first = $results[0] ?? null;

        if (! is_array($first) || ! isset($first['lat'], $first['lon'])) {
            return null;
        }

        return [
            'lat' => (float) $first['lat'],
            'lon' => (float) $first['lon'],
            'displayName' => $first['display_name'] ?? null,
            'raw' => $first,
        ];
    }

    public function searchSuggestions(string $query, int $limit = 5): array
    {
        return array_values(array_map(static function (array $item): array {
            return [
                'lat' => (float) ($item['lat'] ?? 0),
                'lon' => (float) ($item['lon'] ?? 0),
                'displayName' => $item['display_name'] ?? null,
            ];
        }, $this->search($query, $limit)));
    }

    public function reverseGeocode(float $lat, float $lon): ?array
    {
        $cacheKey = $this->reverseCacheKey($lat, $lon);
        $cached = $this->getCachedPayload($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $payload = $this->request('/reverse', [
            'format' => 'json',
            'addressdetails' => 1,
            'lat' => $lat,
            'lon' => $lon,
        ]);

        if (! isset($payload['display_name'])) {
            return null;
        }

        $this->storeCache($cacheKey, $lat, $lon, $payload['display_name'], $payload);

        return $payload;
    }

    private function search(string $query, int $limit): array
    {
        $normalized = $this->normalizeQuery($query);
        if ($normalized === '') {
            return [];
        }

        $cacheKey = $this->searchCacheKey($normalized);
        $cached = $this->getCachedPayload($cacheKey);

        if ($cached !== null) {
            return $cached;
        }

        $payload = $this->request('/search', [
            'format' => 'json',
            'addressdetails' => 1,
            'limit' => $limit,
            'countrycodes' => config('nominatim.country_code', 'ph'),
            'q' => $query,
        ]);

        $results = is_array($payload) ? array_values($payload) : [];
        $this->storeCache($cacheKey, null, null, $results[0]['display_name'] ?? null, $results);

        return $results;
    }

    private function request(string $path, array $query): array
    {
        try {
            return $this->throttle->run(function () use ($path, $query) {
                $response = Http::acceptJson()
                    ->withHeaders(['User-Agent' => config('nominatim.user_agent')])
                    ->timeout((int) config('nominatim.timeout', 10))
                    ->get(rtrim((string) config('nominatim.base_url'), '/').$path, $query);

                if (! $response->successful()) {
                    throw new RuntimeException(sprintf(
                        'Nominatim request failed with HTTP %d for %s.',
                        $response->status(),
                        $path
                    ));
                }

                $payload = $response->json();
                if (! is_array($payload)) {
                    throw new RuntimeException('Nominatim returned an unexpected response.');
                }

                return $payload;
            });
        } catch (ConnectionException $exception) {
            throw new RuntimeException('Nominatim geocoding request failed: '.$exception->getMessage(), previous: $exception);
        } catch (Throwable $exception) {
            throw new RuntimeException('Nominatim geocoding request failed: '.$exception->getMessage(), previous: $exception);
        }
    }

    private function getCachedPayload(string $query): ?array
    {
        $cached = GeocodeCache::query()->where('query', $query)->first();

        return is_array($cached?->raw_response) ? $cached->raw_response : null;
    }

    private function storeCache(string $query, ?float $lat, ?float $lon, ?string $displayName, mixed $rawResponse): void
    {
        GeocodeCache::query()->updateOrCreate(
            ['query' => $query],
            [
                'lat' => $lat,
                'lon' => $lon,
                'display_name' => $displayName,
                'raw_response' => $rawResponse,
                'created_at' => now(),
            ]
        );
    }

    private function normalizeQuery(string $query): string
    {
        return Str::of($query)->squish()->lower()->trim()->toString();
    }

    private function searchCacheKey(string $query): string
    {
        return $query;
    }

    private function reverseCacheKey(float $lat, float $lon): string
    {
        return 'reverse:'.number_format($lat, 5, '.', '').':'.number_format($lon, 5, '.', '');
    }
}
