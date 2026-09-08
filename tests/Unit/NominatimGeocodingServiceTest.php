<?php

namespace Tests\Unit;

use App\Services\NominatimGeocodingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NominatimGeocodingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_geocode_address_hits_nominatim_once_and_uses_cache_after_first_lookup(): void
    {
        Http::fake([
            '*' => Http::response([[
                'lat' => '7.12345',
                'lon' => '125.67890',
                'display_name' => 'Barangay Center, Davao Region',
            ]], 200),
        ]);

        $service = app(NominatimGeocodingService::class);

        $first = $service->geocodeAddress('Barangay Center');
        $second = $service->geocodeAddress('Barangay Center');

        $this->assertSame(7.12345, $first['lat']);
        $this->assertSame('Barangay Center, Davao Region', $first['displayName']);
        $this->assertSame($first, $second);
        Http::assertSentCount(1);
        $this->assertDatabaseCount('geocode_cache', 1);
        $this->assertDatabaseHas('geocode_cache', [
            'query' => 'barangay center',
        ]);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/search')
                && $request->hasHeader('User-Agent')
                && $request->data()['countrycodes'] === 'ph'
                && (string) $request->data()['limit'] === '1';
        });
    }

    public function test_reverse_geocode_uses_rounded_cache_key(): void
    {
        Http::fake([
            '*' => Http::response([
                'display_name' => 'Davao Region, Philippines',
                'address' => ['state' => 'Davao Region'],
            ], 200),
        ]);

        $service = app(NominatimGeocodingService::class);

        $first = $service->reverseGeocode(7.070001, 125.610001);
        $second = $service->reverseGeocode(7.070002, 125.610002);

        $this->assertSame('Davao Region, Philippines', $first['display_name']);
        $this->assertSame($first, $second);
        Http::assertSentCount(1);
        $this->assertDatabaseHas('geocode_cache', [
            'query' => 'reverse:7.07000:125.61000',
        ]);
    }

    public function test_throttle_serializes_consecutive_requests(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::sequence()
                ->push([[
                    'lat' => '7.10000',
                    'lon' => '125.60000',
                    'display_name' => 'First Place',
                ]], 200)
                ->push([[
                    'lat' => '7.20000',
                    'lon' => '125.70000',
                    'display_name' => 'Second Place',
                ]], 200),
        ]);

        $service = app(NominatimGeocodingService::class);
        $start = microtime(true);

        $service->geocodeAddress('First Place');
        $service->geocodeAddress('Second Place');

        $elapsed = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(1.0, $elapsed);
        Http::assertSentCount(2);
    }

    public function test_search_suggestions_returns_cached_results_without_extra_requests(): void
    {
        Http::fake([
            '*' => Http::response([[
                'lat' => '7.12345',
                'lon' => '125.67890',
                'display_name' => 'Barangay Center, Davao Region',
            ], [
                'lat' => '7.22345',
                'lon' => '125.77890',
                'display_name' => 'Nearby Place, Davao Region',
            ]], 200),
        ]);

        $service = app(NominatimGeocodingService::class);

        $service->searchSuggestions('Barangay Center');
        $suggestions = $service->searchSuggestions('Barangay Center');

        $this->assertCount(2, $suggestions);
        Http::assertSentCount(1);
    }
}
