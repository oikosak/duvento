<?php

namespace Tests\Feature;

use App\Models\AssetType;
use App\Models\User;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetListCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_list_shows_cost_per_renewal_period(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', $user);
        $client = $workspace->clients()->create(['name' => 'Πελάτης']);
        $domain = AssetType::query()->where('key', 'domain')->value('id');

        $workspace->assets()->create([
            'client_id' => $client->id, 'asset_type_id' => $domain, 'name' => 'example.gr',
            'expires_at' => now()->addMonths(5), 'renewal_months' => 24,
            'renewal_cost' => '24.60', 'currency' => 'EUR',
        ]);
        $workspace->assets()->create([
            'client_id' => $client->id, 'asset_type_id' => $domain, 'name' => 'nocost.gr',
            'expires_at' => now()->addMonths(5),
        ]);

        $this->actingAs($user)->withSession(['locale' => 'el'])
            ->get(route('assets'))
            ->assertOk()
            ->assertSee('Κόστος')
            ->assertSee('24,60€')
            ->assertSee('/ 2 έτη');
    }

    public function test_client_page_shows_cost_before_status(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', $user);
        $client = $workspace->clients()->create(['name' => 'Πελάτης']);
        $workspace->assets()->create([
            'client_id' => $client->id,
            'asset_type_id' => AssetType::query()->where('key', 'domain')->value('id'),
            'name' => 'example.gr', 'expires_at' => now()->addMonths(5),
            'renewal_months' => 12, 'renewal_cost' => '150', 'currency' => 'EUR',
        ]);

        $this->actingAs($user)->withSession(['locale' => 'el'])
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSeeInOrder(['Πληρώνει', 'Κόστος', 'Κατάσταση'])
            ->assertSee('150€')
            ->assertSee('/ 1 έτος');
    }
}
