<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class NominatimThrottle
{
    private const LOCK_NAME = 'nominatim:throttle:lock';
    private const NEXT_ALLOWED_KEY = 'nominatim:throttle:next_allowed_at';

    public function run(Closure $callback): mixed
    {
        $cache = Cache::store('database');

        return $cache->lock(self::LOCK_NAME, 30)->block(10, function () use ($cache, $callback) {
            $scheduledAt = $this->reserveNextSlot($cache);
            $delayMicros = (int) max(0, ($scheduledAt - microtime(true)) * 1_000_000);

            if ($delayMicros > 0) {
                usleep($delayMicros);
            }

            return $callback();
        });
    }

    private function reserveNextSlot($cache): float
    {
        $now = microtime(true);
        $nextAllowed = (float) $cache->get(self::NEXT_ALLOWED_KEY, 0.0);
        $scheduledAt = max($now, $nextAllowed);

        $cache->put(self::NEXT_ALLOWED_KEY, $scheduledAt + 1.0, now()->addMinutes(10));

        return $scheduledAt;
    }
}
