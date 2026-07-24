<?php

namespace App\Services;

use App\Models\BarangayCentroid;
use App\Models\Beneficiary;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BeneficiaryGeocoder
{
    public function centroidFor(?int $barangayId): ?array
    {
        $barangay = $barangayId ? \App\Models\Barangay::find($barangayId) : null;
        $centroid = $barangay ? BarangayCentroid::where('psgc_code', $barangay->code)->first() : null;

        return $centroid ? ['latitude' => $centroid->latitude, 'longitude' => $centroid->longitude] : null;
    }

    public function locate(Beneficiary $beneficiary): array
    {
        $fallback = $this->centroidFor($beneficiary->barangay_id);
        $address = Str::squish((string) $beneficiary->address);

        if (blank($address)) {
            return $fallback ? $fallback + ['precision' => 'barangay_centroid'] : [];
        }

        $query = Str::lower($address.'|'.$beneficiary->barangay_id);
        $result = Cache::remember('geocode:'.sha1($query), now()->addDays(90), function () use ($address) {
            $response = Http::acceptJson()
                ->withUserAgent('CRISIMAP/1.0 (MSWDO Tagudin AICS System)')
                ->timeout(8)
                ->retry(1, 1200)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'street' => $address,
                    'city' => 'Tagudin',
                    'county' => 'Ilocos Sur',
                    'country' => 'Philippines',
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);

            return $response->successful() ? $response->json()[0] ?? null : null;
        });

        if (is_array($result) && $this->isConfident($result)) {
            return [
                'latitude' => (float) $result['lat'],
                'longitude' => (float) $result['lon'],
                'precision' => 'exact_address',
            ];
        }

        return $fallback ? $fallback + ['precision' => 'barangay_centroid'] : [];
    }

    private function isConfident(array $result): bool
    {
        return isset($result['lat'], $result['lon'])
            && (float) ($result['importance'] ?? 0) >= 0.001
            && ! in_array($result['type'] ?? '', ['country', 'state', 'province'], true);
    }
}
