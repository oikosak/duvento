<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\Index;
use App\Models\AssetType;
use App\Models\User;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuickAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_asset_saves_renewal_period_and_cost(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', $user);
        $workspace->update(['currency' => 'EUR']);
        $client = $workspace->clients()->create(['name' => 'Πελάτης']);
        $this->actingAs($user);

        $this->get(route('dashboard'))->assertOk()->assertSee('quickRenewalMonths');

        Livewire::test(Index::class)
            ->set('quickAssetClientId', $client->id)
            ->set('quickAssetTypeId', AssetType::query()->where('key', 'domain')->value('id'))
            ->set('quickAssetName', 'example.gr')
            ->set('quickExpiresAt', now()->addMonths(3)->toDateString())
            ->set('quickRenewalMonths', 24)
            ->set('quickRenewalCost', '24.60')
            ->call('saveQuickAsset')
            ->assertHasNoErrors();

        $asset = $workspace->assets()->firstOrFail();
        $this->assertSame(24, $asset->renewal_months);
        $this->assertSame('24.60', (string) $asset->renewal_cost);
        $this->assertSame('EUR', $asset->currency);
    }

    public function test_dashboard_upcoming_table_shows_cost_per_period(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', $user);
        $client = $workspace->clients()->create(['name' => 'Πελάτης']);
        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => AssetType::query()->where('key', 'domain')->value('id'),
            'name' => 'example.gr', 'expires_at' => now()->addDays(20),
            'renewal_months' => 24, 'renewal_cost' => '24.60', 'currency' => 'EUR',
        ]);

        $this->actingAs($user)->withSession(['locale' => 'el'])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Τύπος', 'Κόστος', 'Λήξη'])
            ->assertSee('24,60€')
            ->assertSee('/ 2 έτη');
    }
}
