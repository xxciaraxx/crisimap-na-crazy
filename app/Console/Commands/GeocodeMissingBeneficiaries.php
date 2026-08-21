<?php

namespace App\Console\Commands;

use App\Jobs\GeocodeBeneficiary;
use App\Models\Beneficiary;
use Illuminate\Console\Command;

class GeocodeMissingBeneficiaries extends Command
{
    protected $signature = 'crisimap:geocode-missing {--retry-failed : Include records whose previous geocode job failed}';
    protected $description = 'Queue geocoding for beneficiary records without coordinates.';

    public function handle(): int
    {
        $query = Beneficiary::query()->where(fn ($query) => $query->whereNull('latitude')->orWhereNull('longitude'));
        if (! $this->option('retry-failed')) $query->whereNull('geocode_failed_at');
        $count = 0;
        $query->orderBy('id')->chunkById(100, function ($beneficiaries) use (&$count) {
            foreach ($beneficiaries as $beneficiary) {
                GeocodeBeneficiary::dispatch($beneficiary->id);
                $count++;
            }
        });
        $this->info("Queued {$count} beneficiary record(s) for geocoding.");
        return self::SUCCESS;
    }
}
