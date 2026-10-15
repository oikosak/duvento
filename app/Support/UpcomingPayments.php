<?php

namespace App\Support;

use App\Enums\AssetPayer;
use App\Models\Asset;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class UpcomingPayments
{
    public const PERIODS = [7, 30, 90];

    public const TREND_MONTHS = 12;

    public const CURRENCIES = ['USD', 'EUR', 'GBP', 'RUB', 'UAH', 'PLN', 'KZT', 'CZK'];

    public static function constrain(mixed $query, int $days): mixed
    {
        [$from, $until] = self::window($days);

        return $query
            ->whereNotNull('renewal_cost')
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '>=', $from->toDateString())
            ->whereDate('expires_at', '<=', $until->toDateString());
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array{
     *     days: int,
     *     count: int,
     *     by_currency: array<string, string>,
     *     by_payer: array<string, array<string, string>>,
     *     total_label: string,
     *     payer_labels: array<string, string>,
     *     default_currency: string,
     *     trend: array{months: list<array<string, mixed>>, max: float}
     * }
     */
    public static function summarize(Collection $assets, int $days, string $fallbackCurrency): array
    {
        $days = self::normalizePeriod($days);
        $fallback = self::normalizeCurrency($fallbackCurrency);
        [$from, $until] = self::window($days);

        $eligible = $assets
            ->filter(fn (Asset $asset) => self::isDue($asset, $from, $until))
            ->values();

        $byCurrency = [];
        $byPayer = [
            AssetPayer::Agency->value => [],
            AssetPayer::Client->value => [],
            AssetPayer::Unknown->value => [],
        ];

        foreach ($eligible as $asset) {
            $currency = self::normalizeCurrency($asset->currency ?: $fallback);
            $cost = (string) $asset->renewal_cost;
            $payer = $asset->payer?->value ?? AssetPayer::Unknown->value;
            $byCurrency[$currency] = self::add($byCurrency[$currency] ?? '0', $cost);
            $byPayer[$payer][$currency] = self::add($byPayer[$payer][$currency] ?? '0', $cost);
        }

        ksort($byCurrency);
        foreach ($byPayer as &$bucket) {
            ksort($bucket);
        }
        unset($bucket);

        return [
            'days' => $days,
            'count' => $eligible->count(),
            'by_currency' => $byCurrency,
            'by_payer' => $byPayer,
            'total_label' => self::join($byCurrency),
            'payer_labels' => [
                AssetPayer::Agency->value => self::join($byPayer[AssetPayer::Agency->value], true),
                AssetPayer::Client->value => self::join($byPayer[AssetPayer::Client->value], true),
                AssetPayer::Unknown->value => self::join($byPayer[AssetPayer::Unknown->value], true),
            ],
            'default_currency' => $fallback,
            'trend' => self::trend($assets, $fallback),
        ];
    }

    /**
     * @param  Collection<int, Asset>  $assets
     * @return array{months: list<array{key: string, label: string, by_currency: array<string, string>, total_label: string, bars: list<array{currency: string, amount: string, label: string, height: int}>}>, max: float}
     */
    public static function trend(Collection $assets, string $fallbackCurrency, int $months = self::TREND_MONTHS): array
    {
        $months = max(3, min(12, $months));
        $fallback = self::normalizeCurrency($fallbackCurrency);
        $from = now()->startOfDay();
        $origin = now()->startOfMonth();
        $until = $origin->copy()->addMonths($months)->subDay()->startOfDay();

        $buckets = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $origin->copy()->addMonths($i);
            $key = $month->format('Y-m');
            $buckets[$key] = [
                'key' => $key,
                'label' => $month->translatedFormat('M'),
                'by_currency' => [],
            ];
        }

        foreach ($assets as $asset) {
            if ($asset->renewal_cost === null || $asset->expires_at === null) {
                continue;
            }

            $currency = self::normalizeCurrency($asset->currency ?: $fallback);

            // Every renewal inside the window, so short periods (1–6 months) recur.
            foreach (self::occurrences($asset, $from, $until) as $date) {
                $key = $date->format('Y-m');

                if (! isset($buckets[$key])) {
                    continue;
                }

                $buckets[$key]['by_currency'][$currency] = self::add(
                    $buckets[$key]['by_currency'][$currency] ?? '0',
                    (string) $asset->renewal_cost,
                );
            }
        }

        $max = 0.0;

        foreach ($buckets as $bucket) {
            foreach ($bucket['by_currency'] as $amount) {
                $max = max($max, (float) $amount);
            }
        }

        $out = [];

        foreach ($buckets as $bucket) {
            ksort($bucket['by_currency']);
            $bars = [];

            foreach ($bucket['by_currency'] as $code => $amount) {
                $height = $max > 0 ? (int) round(((float) $amount / $max) * 100) : 0;
                $bars[] = [
                    'currency' => $code,
                    'amount' => $amount,
                    'label' => self::format($code, $amount),
                    'height' => $amount === '0.00' ? 0 : max($height, 8),
                ];
            }

            $out[] = [
                'key' => $bucket['key'],
                'label' => $bucket['label'],
                'by_currency' => $bucket['by_currency'],
                'total_label' => self::join($bucket['by_currency']),
                'bars' => $bars,
            ];
        }

        return [
            'months' => $out,
            'max' => $max,
        ];
    }

    public static function format(string $currency, int|float|string $amount): string
    {
        $currency = self::normalizeCurrency($currency);
        // Greek: 1.234,50 — elsewhere: 1 234.50
        [$decimal, $thousands] = app()->getLocale() === 'el' ? [',', '.'] : ['.', ' '];
        $value = number_format((float) $amount, 2, $decimal, $thousands);

        if (str_ends_with($value, $decimal.'00')) {
            $value = substr($value, 0, -3);
        }

        return match ($currency) {
            'USD' => '$'.$value,
            // Greek convention puts the euro sign after the amount: 150€
            'EUR' => app()->getLocale() === 'el' ? $value.'€' : '€'.$value,
            'GBP' => '£'.$value,
            'RUB' => $value.' ₽',
            'UAH' => $value.' ₴',
            'PLN' => $value.' zł',
            'KZT' => $value.' ₸',
            'CZK' => $value.' Kč',
            default => $value.' '.$currency,
        };
    }

    /**
     * Renewal dates of an asset between $from and $until (inclusive), following its renewal period.
     *
     * @return list<Carbon>
     */
    public static function occurrences(Asset $asset, Carbon $from, Carbon $until): array
    {
        if ($asset->expires_at === null) {
            return [];
        }

        $months = max(1, (int) ($asset->renewal_months ?: 12));
        $date = $asset->expires_at->copy()->startOfDay();
        $dates = [];

        while ($date->lte($until)) {
            if ($date->gte($from)) {
                $dates[] = $date->copy();
            }

            $date = $date->addMonthsNoOverflow($months);
        }

        return $dates;
    }

    /** @param  array<string, string>  $totals */
    public static function join(array $totals, bool $skipZero = false): string
    {
        return collect($totals)
            ->filter(fn (string $amount) => ! $skipZero || self::add($amount, '0') !== '0.00')
            ->map(fn (string $amount, string $currency) => self::format($currency, $amount))
            ->implode(', ');
    }

    public static function normalizePeriod(int|string $days): int
    {
        $days = (int) $days;

        return in_array($days, self::PERIODS, true) ? $days : 30;
    }

    public static function normalizeCurrency(?string $currency): string
    {
        $currency = strtoupper(trim((string) $currency));

        return preg_match('/^[A-Z]{3}$/', $currency) === 1 ? $currency : 'USD';
    }

    public static function add(string $left, string $right): string
    {
        return number_format((float) $left + (float) $right, 2, '.', '');
    }

    /** @return array{Carbon, Carbon} */
    private static function window(int $days): array
    {
        $from = now()->startOfDay();

        return [$from, $from->copy()->addDays($days)];
    }

    private static function isDue(Asset $asset, Carbon $from, Carbon $until): bool
    {
        if ($asset->renewal_cost === null || $asset->expires_at === null) {
            return false;
        }

        $expires = $asset->expires_at->copy()->startOfDay();

        return $expires->gte($from) && $expires->lte($until);
    }
}
