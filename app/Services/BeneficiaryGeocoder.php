<?php

namespace App\Services;

use App\Models\BarangayCentroid;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class BeneficiaryGeocoder
{
    public function __construct(
        private readonly NominatimThrottle $throttle,
        private readonly GeoapifyGeocodingService $geoapify,
        private readonly BarangayBoundaryLocator $boundaryLocator,
    ) {
    }

    public function centroidFor(?int $barangayId): ?array
    {
        $barangay = $barangayId ? \App\Models\Barangay::find($barangayId) : null;
        $centroid = $barangay ? BarangayCentroid::where('psgc_code', $barangay->code)->first() : null;

        return $centroid ? ['latitude' => $centroid->latitude, 'longitude' => $centroid->longitude] : null;
    }

    /** Give centroid fallbacks a small, repeatable offset so records remain selectable on the map. */
    public function jitteredCentroidFor(?int $barangayId, string|int|null $seed): ?array
    {
        $centroid = $this->centroidFor($barangayId);
        if (! $centroid) {
            return null;
        }

        $hash = hash('sha256', (string) $seed);
        $latOffset = ((hexdec(substr($hash, 0, 4)) / 65535) - .5) * .0016;
        $lngOffset = ((hexdec(substr($hash, 4, 4)) / 65535) - .5) * .0016;

        return [
            'latitude' => round((float) $centroid['latitude'] + $latOffset, 7),
            'longitude' => round((float) $centroid['longitude'] + $lngOffset, 7),
        ];
    }

    public function locate(Beneficiary $beneficiary): array
    {
        $fallback = $this->jitteredCentroidFor($beneficiary->barangay_id, $beneficiary->id ?: $beneficiary->history_key);
        $address = Str::squish((string) $beneficiary->address);
        $barangay = Str::squish((string) $beneficiary->barangay?->name);

        if (blank($address)) {
            return $fallback ? $fallback + ['precision' => 'barangay_centroid'] : [];
        }

        $search = implode(', ', array_filter([$address, $barangay, 'Tagudin', 'Ilocos Sur', 'Philippines']));
        try {
            if (filled(config('services.geoapify.key'))) {
                $result = $this->geoapify->geocode($search);
            } else {
                $query = Str::lower(implode('|', [$address, $barangay, 'Tagudin', 'Ilocos Sur', 'Philippines']));
                $result = Cache::remember('geocode:'.sha1($query), now()->addDays(90), function () use ($search) {
                    return $this->throttle->run(function () use ($search) {
                        $response = Http::acceptJson()
                            ->withHeaders(['User-Agent' => config('nominatim.user_agent')])
                            ->timeout((int) config('nominatim.timeout', 10))
                            ->retry(1, 1200)
                            ->get(rtrim((string) config('nominatim.base_url'), '/').'/search', [
                                'q' => $search,
                                'countrycodes' => config('nominatim.country_code', 'ph'),
                                'format' => 'jsonv2',
                                'limit' => 1,
                            ]);

                        return $response->successful() ? $response->json()[0] ?? null : null;
                    });
                });
            }
        } catch (\Throwable $exception) {
            Log::warning('Beneficiary geocoding request failed.', [
                'beneficiary_id' => $beneficiary->id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        if (is_array($result)) {
            $latitude = $result['latitude'] ?? $result['lat'] ?? null;
            $longitude = $result['longitude'] ?? $result['lon'] ?? null;
            if (is_numeric($latitude) && is_numeric($longitude)) {
                $location = [
                    'latitude' => (float) $latitude,
                    'longitude' => (float) $longitude,
                    'precision' => $result['precision'] ?? ($this->isExactAddress($result) ? 'exact_address' : 'place_result'),
                ];
                $barangayMatch = $this->boundaryLocator->find($location['latitude'], $location['longitude']);
                if ($barangayMatch) {
                    $location['barangay_id'] = $barangayMatch->id;
                }

                return $location;
            }
        }

        return $fallback ? $fallback + ['precision' => 'barangay_centroid'] : [];
    }

    private function hasCoordinates(array $result): bool
    {
        return isset($result['lat'], $result['lon'])
            && is_numeric($result['lat'])
            && is_numeric($result['lon']);
    }

    private function isExactAddress(array $result): bool
    {
        return isset($result['address']['house_number'])
            && ! in_array($result['type'] ?? '', ['country', 'state', 'province', 'town', 'village', 'municipality'], true);
    }
}
