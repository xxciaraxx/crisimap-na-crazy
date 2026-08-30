<?php

namespace Tests\Feature;

use App\Jobs\GeocodeBeneficiary;
use App\Models\Beneficiary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class GeocodeMissingBeneficiariesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_missing_locations_but_not_failed_or_manual_records_by_default(): void
    {
        Bus::fake();
        $pending = $this->beneficiary();
        $failed = $this->beneficiary(['geocode_failed_at' => now()]);
        $manual = $this->beneficiary(['geocode_precision' => 'manual']);

        $this->artisan('crisimap:geocode-missing')->assertSuccessful();

        Bus::assertDispatched(GeocodeBeneficiary::class, fn (GeocodeBeneficiary $job) => $job->beneficiaryId === $pending->id);
        Bus::assertNotDispatched(GeocodeBeneficiary::class, fn (GeocodeBeneficiary $job) => in_array($job->beneficiaryId, [$failed->id, $manual->id], true));
    }

    public function test_retry_failed_queues_failed_records_but_never_manual_pins(): void
    {
        Bus::fake();
        $failed = $this->beneficiary(['geocode_failed_at' => now()]);
        $manual = $this->beneficiary(['geocode_precision' => 'manual', 'geocode_failed_at' => now()]);

        $this->artisan('crisimap:geocode-missing --retry-failed')->assertSuccessful();

        Bus::assertDispatched(GeocodeBeneficiary::class, fn (GeocodeBeneficiary $job) => $job->beneficiaryId === $failed->id);
        Bus::assertNotDispatched(GeocodeBeneficiary::class, fn (GeocodeBeneficiary $job) => $job->beneficiaryId === $manual->id);
    }

    private function beneficiary(array $attributes = []): Beneficiary
    {
        return Beneficiary::create(array_merge([
            'name' => 'Test Beneficiary '.str()->random(8),
            'age' => 35,
            'sex' => 'Female',
            'status' => 'Released',
        ], $attributes));
    }
}
