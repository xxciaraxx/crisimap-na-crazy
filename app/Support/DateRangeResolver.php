<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class DateRangeResolver
{
    public static function resolve(?string $period): ?array
    {
        if (blank($period)) {
            return null;
        }

        try {
            if (preg_match('/^(\d{4})$/', $period, $matches)) {
                $date = CarbonImmutable::create((int) $matches[1], 1, 1);
                return [$date->startOfYear(), $date->endOfYear()];
            }
            if (preg_match('/^(\d{4})-Q([1-4])$/', $period, $matches)) {
                $date = CarbonImmutable::create((int) $matches[1], ((int) $matches[2] - 1) * 3 + 1, 1);
                return [$date->startOfQuarter(), $date->endOfQuarter()];
            }
            if (preg_match('/^(\d{4})-(\d{2})$/', $period)) {
                $date = CarbonImmutable::createFromFormat('Y-m', $period);
                return [$date->startOfMonth(), $date->endOfMonth()];
            }
            if (preg_match('/^(\d{4})-W(\d{2})$/', $period, $matches)) {
                $date = CarbonImmutable::now()->setISODate((int) $matches[1], (int) $matches[2]);
                return [$date->startOfWeek(), $date->endOfWeek()];
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
                $date = CarbonImmutable::parse($period);
                return [$date->startOfDay(), $date->endOfDay()];
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
