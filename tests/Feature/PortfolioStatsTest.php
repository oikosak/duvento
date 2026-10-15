<?php

namespace Tests\Feature;

use App\Models\AssetType;
use App\Models\User;
use App\Support\PortfolioStats;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PortfolioStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_yearly_value_next12_and_top_clients(): void
    {
        Carbon::setTestNow('2026-10-15 10:00:00');
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $ws = app(WorkspaceProvisioner::class)->create('2bit', $user);
        $ws->update(['currency' => 'EUR']);
        $a = $ws->clients()->create(['name' => 'Α']);
        $b = $ws->clients()->create(['name' => 'Β']);
        $domain = AssetType::query()->where('key', 'domain')->value('id');
        $hosting = AssetType::query()->where('key', 'hosting')->value('id');
        $mk = fn ($client, $type, $date, $months, $cost) => $ws->assets()->create([
            'client_id' => $client->id, 'asset_type_id' => $type, 'name' => 'x', 'expires_at' => $date,
            'renewal_months' => $months, 'renewal_cost' => $cost, 'currency' => 'EUR',
        ]);

        $mk($a, $domain, '2027-01-09', 24, 30);   // yearly 15, due once in window
        $mk($a, $hosting, '2027-01-09', 12, 150); // yearly 150, due once
        $mk($b, $domain, '2028-05-01', 24, 30);   // yearly 15, not due in window
        $mk($b, $hosting, '2026-11-01', 3, 25);   // yearly 100, due 4 times (Nov, Feb, May, Aug)
        $ws->assets()->create(['client_id' => $b->id, 'asset_type_id' => $domain, 'name' => 'free', 'expires_at' => '2027-02-01']);

        app()->setLocale('el');
        $s = PortfolioStats::summarize($ws->assets()->with('client')->get(), 'EUR');

        $this->assertSame(['EUR' => '280.00'], $s['annual']);
        $this->assertSame(['EUR' => '280.00'], $s['next12']);
        $this->assertSame(6, $s['next12_count']);
        $this->assertSame(1, $s['unpriced']);
        $this->assertSame(['Α', 'Β'], array_column($s['top_clients'], 'name'));
        $this->assertSame('165€', $s['top_clients'][0]['label']);

        $this->actingAs($user)->withSession(['locale' => 'el'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Ετήσια εικόνα')
            ->assertSee('280€')
            ->assertSee('1 υπηρεσία δεν έχει κόστος');
    }
}
