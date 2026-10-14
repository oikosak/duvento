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
}
