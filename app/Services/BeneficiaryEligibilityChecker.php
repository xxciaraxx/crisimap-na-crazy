<?php

namespace App\Services;

use App\Models\Beneficiary;
use Carbon\Carbon;

class BeneficiaryEligibilityChecker
{
    public function for(Beneficiary $candidate, ?Beneficiary $ignore = null): array
    {
        $historyQuery = Beneficiary::query()->where('history_key', $candidate->history_key);
        if ($ignore) $historyQuery->where('id', '!=', $ignore->id);
        // The restriction begins only once assistance was actually released,
        // not when an application was merely approved.
        $latest = (clone $historyQuery)->whereIn('status', ['Released', 'Completed'])->whereNotNull('date_of_assistance')->orderByDesc('date_of_assistance')->orderByDesc('created_at')->first();
        $latestDate = $latest?->date_of_assistance ?? $latest?->created_at;
        $daysSince = $latestDate?->diffInDays(now());
        return ['eligible' => $daysSince === null || $daysSince >= 90, 'duplicate_count' => $historyQuery->count(), 'days_since_last_assistance' => $daysSince, 'next_eligible_at' => $latestDate ? Carbon::parse($latestDate)->addDays(90) : null, 'latest' => $latest];
    }

    public function apply(Beneficiary $beneficiary): Beneficiary
    {
        $eligibility = $this->for($beneficiary, $beneficiary->exists ? $beneficiary : null);
        $beneficiary->forceFill(['eligibility_checked_at' => now(), 'eligibility_status' => $eligibility['eligible'] ? 'Eligible' : 'Ineligible'])->save();
        return $beneficiary;
    }
}
