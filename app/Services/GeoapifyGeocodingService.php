<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeoapifyGeocodingService
{
    public function geocode(string $address): ?array
    {
        $result = $this->search($address, 1)[0] ?? null;
        if (! is_array($result) || ! isset($result['lat'], $result['lon'])) {
            return null;
        }

        return [
            'latitude' => (float) $result['lat'],
            'longitude' => (float) $result['lon'],
            'precision' => $this->precision($result),
        ];
    }

    public function searchSuggestions(string $query, int $limit = 5): array
    {
        return array_values(array_map(static function (array $result): array {
            return [
                'lat' => (float) ($result['lat'] ?? 0),
                'lon' => (float) ($result['lon'] ?? 0),
                'displayName' => $result['formatted'] ?? $result['address_line1'] ?? null,
            ];
        }, $this->search($query, $limit)));
    }

    private function search(string $address, int $limit): array
    {
        $response = Http::acceptJson()
            ->timeout((int) config('services.geoapify.timeout', 10))
            ->retry(2, 300)
            ->get('https://api.geoapify.com/v1/geocode/search', [
                'text' => $address,
                'filter' => 'countrycode:ph',
                'bias' => 'proximity:120.4442,16.9355',
                'limit' => $limit,
                'apiKey' => config('services.geoapify.key'),
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Geoapify request failed with HTTP '.$response->status().'.');
        }

        $results = $response->json('results');

        return is_array($results) ? $results : [];
    }

    private function precision(array $result): string
    {
        if (($result['confidence_building_level'] ?? 0) > 0) {
            return 'exact_address';
        }

        return ($result['confidence_street_level'] ?? 0) > 0 ? 'street_result' : 'place_result';
    }
}
