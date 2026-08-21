<?php

namespace App\Jobs;

use App\Models\Beneficiary;
use App\Services\BeneficiaryGeocoder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\Middleware\RateLimited;
use Throwable;

class GeocodeBeneficiary implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function middleware(): array
    {
        return [new RateLimited('nominatim')];
    }

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

        $beneficiary->forceFill(['geocode_attempted_at' => now(), 'geocode_failed_at' => null])->saveQuietly();
        try {
            $location = $geocoder->locate($beneficiary);
        } catch (Throwable $exception) {
            $beneficiary->forceFill(['geocode_failed_at' => now(), 'geocode_failure_reason' => mb_substr($exception->getMessage(), 0, 1000)])->saveQuietly();
            if ($this->attempts() < $this->tries) {
                $delays = $this->backoff();
                $this->release($delays[min($this->attempts() - 1, count($delays) - 1)]);
                return;
            }

            throw $exception;
        }
        if ($location !== []) {
            $beneficiary->forceFill([
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'geocode_precision' => $location['precision'],
                'geocoded_at' => now(),
            ])->saveQuietly();
        }
    }

    public function failed(Throwable $exception): void
    {
        Beneficiary::whereKey($this->beneficiaryId)->update([
            'geocode_failed_at' => now(),
            'geocode_failure_reason' => mb_substr($exception->getMessage(), 0, 1000),
        ]);
    }
}
