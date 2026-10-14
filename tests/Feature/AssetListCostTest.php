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
            ->assertSee('€24.60')
            ->assertSee('/ 2 έτη');
    }
}
