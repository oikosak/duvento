<?php

namespace App\Support;

use App\Models\Asset;
use Illuminate\Support\Collection;

/**
 * Yearly view of what the workspace bills for renewals.
 * "renewal_cost" is what the client is charged per renewal period.
 */
final class PortfolioStats
{
    public const TOP_CLIENTS = 5;

    /**
     * @param  Collection<int, Asset>  $assets  with the "client" relation loaded
     * @return array{annual: array<string, string>, annual_label: string, next12: array<string, string>, next12_label: string, next12_count: int, priced: int, unpriced: int, top_clients: list<array{id: int, name: string, label: string}>}
     */
    public static function summarize(Collection $assets, string $fallbackCurrency): array
    {
        $fallback = UpcomingPayments::normalizeCurrency($fallbackCurrency);
        $from = now()->startOfDay();
        $until = $from->copy()->addYear()->subDay();

        $annual = [];
        $next12 = [];
        $next12Count = 0;
        $perClient = [];

        $priced = $assets->filter(fn (Asset $asset) => $asset->renewal_cost !== null);

        foreach ($priced as $asset) {
            $currency = UpcomingPayments::normalizeCurrency($asset->currency ?: $fallback);
            $months = max(1, (int) ($asset->renewal_months ?: 12));
            // Spread multi-year renewals over their years: 30€ / 2 years = 15€ a year.
            $yearly = number_format((float) $asset->renewal_cost * 12 / $months, 2, '.', '');

            $annual[$currency] = UpcomingPayments::add($annual[$currency] ?? '0', $yearly);

            foreach (UpcomingPayments::occurrences($asset, $from, $until) as $ignored) {
                $next12[$currency] = UpcomingPayments::add($next12[$currency] ?? '0', (string) $asset->renewal_cost);
                $next12Count++;
            }

            if ($asset->client) {
                $id = $asset->client->id;
                $perClient[$id] ??= ['id' => $id, 'name' => $asset->client->name, 'amounts' => []];
                $perClient[$id]['amounts'][$currency] = UpcomingPayments::add($perClient[$id]['amounts'][$currency] ?? '0', $yearly);
            }
        }

        ksort($annual);
        ksort($next12);

        $top = collect($perClient)
            ->sortByDesc(fn (array $row) => (float) ($row['amounts'][$fallback] ?? 0) + array_sum(array_map('floatval', $row['amounts'])) / 1e6)
            ->take(self::TOP_CLIENTS)
            ->map(fn (array $row) => [
                'id' => $row['id'],
                'name' => $row['name'],
                'label' => UpcomingPayments::join(collect($row['amounts'])->sortKeys()->all()),
            ])
            ->values()
            ->all();

        return [
            'annual' => $annual,
            'annual_label' => UpcomingPayments::join($annual),
            'next12' => $next12,
            'next12_label' => UpcomingPayments::join($next12),
            'next12_count' => $next12Count,
            'priced' => $priced->count(),
            'unpriced' => $assets->count() - $priced->count(),
            'top_clients' => $top,
        ];
    }
}
