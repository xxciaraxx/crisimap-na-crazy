<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BarangayCentroid;
use App\Services\GeoapifyGeocodingService;
use App\Services\NominatimGeocodingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GeocodeController extends Controller
{
    public function suggest(
        Request $request,
        NominatimGeocodingService $nominatim,
        GeoapifyGeocodingService $geoapify,
    ): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        try {
            $suggestions = filled(config('services.geoapify.key'))
                ? $geoapify->searchSuggestions($data['q'], 5)
                : $nominatim->searchSuggestions($data['q'], 5);

            if ($suggestions === []) {
                $suggestions = $this->barangayCentroidSuggestion($data['q']);
            }

            return response()->json($suggestions);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Geocoding is temporarily unavailable. You can still drop a pin manually.',
                'suggestions' => [],
            ], 503);
        }
    }

    private function barangayCentroidSuggestion(string $query): array
    {
        $normalizedQuery = Str::lower(Str::squish($query));
        $centroid = BarangayCentroid::query()
            ->get(['barangay', 'latitude', 'longitude'])
            ->first(fn (BarangayCentroid $item) => Str::contains($normalizedQuery, Str::lower($item->barangay)));

        return $centroid ? [[
            'lat' => (float) $centroid->latitude,
            'lon' => (float) $centroid->longitude,
            'displayName' => $centroid->barangay.', Tagudin, Ilocos Sur, Philippines',
        ]] : [];
    }

    public function reverse(Request $request, NominatimGeocodingService $geocoder): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric'],
            'lon' => ['required', 'numeric'],
        ]);

        try {
            $result = $geocoder->reverseGeocode((float) $data['lat'], (float) $data['lon']);
        } catch (\Throwable $exception) {
            return response()->json([
                'message' => 'Reverse geocoding is temporarily unavailable. You can still save the pin.',
            ], 503);
        }

        return response()->json([
            'displayName' => is_array($result) ? ($result['display_name'] ?? null) : null,
            'address' => is_array($result) ? ($result['address'] ?? null) : null,
            'raw' => $result,
        ]);
    }
}
