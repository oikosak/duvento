<?php

namespace Tests\Feature;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Support\SystemCatalog;
use App\Support\WorkspaceInviter;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InviteRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_invite_link_opens_registration_page(): void
    {
        Notification::fake();
        SystemCatalog::ensureAssetTypes();
        $owner = User::factory()->create();
        $workspace = app(WorkspaceProvisioner::class)->create('Partner', $owner);
        app(WorkspaceInviter::class)->invite($workspace, 'partner@example.test', WorkspaceRole::Owner);
        $invite = WorkspaceInvitation::query()->where('email', 'partner@example.test')->firstOrFail();

        $this->get(route('invites.show', $invite->token))->assertRedirect(route('register', ['invite' => $invite->token]));

        $this->get(route('register', ['invite' => $invite->token]))->assertOk();
    }

    public function test_public_register_is_404(): void
    {
        $this->get('/register')->assertNotFound();
    }
}
