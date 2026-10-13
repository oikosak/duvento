<?php

namespace App\Support;

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Formats dates for display only, using the per-locale formats in lang/{locale}/app.php
 * (date_format / datetime_format). Stored values and form inputs stay ISO (Y-m-d).
 */
class DisplayDate
{
    public static function date(CarbonInterface|string|null $value): ?string
    {
        return self::render($value, 'app.date_format', 'Y-m-d');
    }

    public static function dateTime(CarbonInterface|string|null $value): ?string
    {
        return self::render($value, 'app.datetime_format', 'Y-m-d H:i');
    }

    private static function render(CarbonInterface|string|null $value, string $key, string $fallback): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            try {
                $value = Carbon::parse($value);
            } catch (\Throwable) {
                return $value;
            }
        }

        $format = __($key);

        return $value->format($format === $key ? $fallback : $format);
    }
}
