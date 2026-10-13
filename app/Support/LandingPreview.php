<?php

namespace App\Support;

use App\Enums\AssetStatus;
use Illuminate\Support\Collection;

final class LandingPreview
{
    public static function assets(): Collection
    {
        return collect([
            self::item(__('app.landing.demo.site'), __('app.landing.demo.client_a'), __('app.asset_types.ssl'), 4, AssetStatus::Critical),
            self::item(__('app.landing.demo.site'), __('app.landing.demo.client_a'), __('app.asset_types.domain'), 12, AssetStatus::Urgent),
            self::item('Yoast SEO Premium', __('app.landing.demo.client_a'), __('app.asset_types.plugin_license'), 22, AssetStatus::Upcoming),
            self::item(__('app.landing.demo.insurance'), __('app.landing.demo.client_b'), __('app.landing.demo.insurance_type'), 45, AssetStatus::Ok),
        ]);
    }

    private static function item(string $name, string $client, string $type, int $days, AssetStatus $status): object
    {
        return (object) [
            'name' => $name,
            'client' => (object) ['name' => $client],
            'assetType' => new class($type)
            {
                public function __construct(public string $label) {}

                public function displayLabel(): string
                {
                    return $this->label;
                }
            },
            'days_left' => $days,
            'status' => $status,
        ];
    }
}
