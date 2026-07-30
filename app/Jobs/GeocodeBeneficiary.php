<?php

namespace App\Jobs;

use App\Models\Beneficiary;
use App\Services\BeneficiaryGeocoder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GeocodeBeneficiary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $beneficiaryId)
    {
        $this->onQueue('geocoding');
    }

    public function handle(BeneficiaryGeocoder $geocoder): void
    {
        $beneficiary = Beneficiary::find($this->beneficiaryId);
        if (! $beneficiary || $beneficiary->geocode_precision === 'manual') {
            return;
        }

        $location = $geocoder->locate($beneficiary);
        if ($location !== []) {
            $beneficiary->forceFill([
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'geocode_precision' => $location['precision'],
                'geocoded_at' => now(),
            ])->saveQuietly();
        }
    }
}
