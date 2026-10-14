<?php

namespace Tests\Feature;

use App\Livewire\Assets\Form;
use App\Models\AssetType;
use App\Models\User;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ActivityLabelTest extends TestCase
{
    use RefreshDatabase;

    public function test_asset_page_shows_translated_activity_label(): void
    {
        SystemCatalog::ensureAssetTypes();
        $user = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', $user);
        $client = $workspace->clients()->create(['name' => 'Πελάτης']);
        $this->actingAs($user)->withSession(['locale' => 'el']);
        app()->setLocale('el');

        Livewire::test(Form::class)
            ->set('formClientId', $client->id)
            ->set('assetTypeId', AssetType::query()->where('key', 'domain')->value('id'))
            ->set('name', 'example.gr')
            ->set('expiresAt', now()->addDays(30)->toDateString())
            ->call('save');

        $asset = $workspace->assets()->firstOrFail();

        $this->get(route('assets.show', $asset))
            ->assertOk()
            ->assertSee('Δημιουργήθηκε υπηρεσία')
            ->assertDontSee('app.activity.actions');
    }
}
