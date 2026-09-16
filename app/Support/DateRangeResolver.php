<?php

namespace App\Support;

use Carbon\CarbonImmutable;

class DateRangeResolver
{
    public static function resolve(?string $period): ?array
    {
        $period = is_string($period) ? trim($period) : null;
        $timezone = config('app.timezone', 'Asia/Manila');
        if (blank($period)) {
            return null;
        }

        try {
            if (preg_match('/^(\d{4})$/', $period, $matches)) {
                $date = CarbonImmutable::createSafe((int) $matches[1], 1, 1, 0, 0, 0, $timezone);
                return [$date->startOfYear(), $date->endOfYear()];
            }
            if (preg_match('/^(\d{4})-Q([1-4])$/', $period, $matches)) {
                $date = CarbonImmutable::createSafe((int) $matches[1], ((int) $matches[2] - 1) * 3 + 1, 1, 0, 0, 0, $timezone);
                return [$date->startOfQuarter(), $date->endOfQuarter()];
            }
            if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $period)) {
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $period.'-01', $timezone);
                return [$date->startOfMonth(), $date->endOfMonth()];
            }
            if (preg_match('/^(\d{4})-W(0[1-9]|[1-4]\d|5[0-3])$/', $period, $matches)) {
                $date = CarbonImmutable::createSafe((int) $matches[1], 1, 1, 0, 0, 0, $timezone)
                    ->setISODate((int) $matches[1], (int) $matches[2], 1);
                if ((int) $date->isoWeekYear !== (int) $matches[1] || (int) $date->isoWeek !== (int) $matches[2]) {
                    return null;
                }
                return [$date->startOfWeek(), $date->endOfWeek()];
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $period)) {
                $date = CarbonImmutable::createFromFormat('!Y-m-d', $period, $timezone);
                if ($date->format('Y-m-d') !== $period) {
                    return null;
                }
                return [$date->startOfDay(), $date->endOfDay()];
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
