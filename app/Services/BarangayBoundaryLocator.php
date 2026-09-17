<?php

namespace App\Services;

use App\Models\Barangay;
use Illuminate\Support\Facades\File;

class BarangayBoundaryLocator
{
    public function find(float $latitude, float $longitude): ?Barangay
    {
        $path = public_path('geo/barangay-boundaries.geojson');
        if (! File::exists($path)) {
            return null;
        }

        $geojson = json_decode(File::get($path), true);
        foreach ($geojson['features'] ?? [] as $feature) {
            $properties = $feature['properties'] ?? [];
            $code = (string) ($properties['adm4_psgc'] ?? '');
            $geometry = $feature['geometry'] ?? [];

            if ($code !== '' && $this->contains($geometry, $longitude, $latitude)) {
                return Barangay::query()->where('code', $code)->first();
            }
        }

        return null;
    }

    private function contains(array $geometry, float $longitude, float $latitude): bool
    {
        $polygons = $geometry['type'] === 'Polygon'
            ? [$geometry['coordinates'] ?? []]
            : ($geometry['coordinates'] ?? []);

        foreach ($polygons as $polygon) {
            if ($this->inRing($polygon[0] ?? [], $longitude, $latitude)
                && ! $this->inAnyRing(array_slice($polygon, 1), $longitude, $latitude)) {
                return true;
            }
        }

        return false;
    }

    private function inAnyRing(array $rings, float $longitude, float $latitude): bool
    {
        foreach ($rings as $ring) {
            if ($this->inRing($ring, $longitude, $latitude)) {
                return true;
            }
        }

        return false;
    }

    private function inRing(array $ring, float $longitude, float $latitude): bool
    {
        $inside = false;
        $count = count($ring);

        for ($index = 0, $previous = $count - 1; $index < $count; $previous = $index++) {
            $point = $ring[$index] ?? [];
            $last = $ring[$previous] ?? [];
            if (count($point) < 2 || count($last) < 2) {
                continue;
            }

            [$pointLongitude, $pointLatitude] = $point;
            [$lastLongitude, $lastLatitude] = $last;
            $crosses = (($pointLatitude > $latitude) !== ($lastLatitude > $latitude))
                && ($longitude < ($lastLongitude - $pointLongitude) * ($latitude - $pointLatitude)
                    / (($lastLatitude - $pointLatitude) ?: PHP_FLOAT_EPSILON) + $pointLongitude);

            if ($crosses) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }
}
