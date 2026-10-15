<?php

namespace Tests\Feature;

use App\Support\CsvImporter;
use App\Support\PlanGuard;
use App\Support\SystemCatalog;
use App\Support\WorkspaceProvisioner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CsvImportGuardTest extends TestCase
{
    use RefreshDatabase;

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('f.csv', $body);
    }

    public function test_assets_file_is_rejected_as_clients_and_creates_nothing(): void
    {
        SystemCatalog::ensureAssetTypes();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', User::factory()->create());

        try {
            app(CsvImporter::class)->importClients($workspace, $this->csv("name,type,client,expires_at\nexample.gr,domain,Πελάτης,2027-01-01\n"), app(PlanGuard::class));
            $this->fail('expected rejection');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('file', $e->errors());
        }

        $this->assertSame(0, $workspace->clients()->count());
    }

    public function test_clients_file_is_rejected_as_assets(): void
    {
        SystemCatalog::ensureAssetTypes();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', User::factory()->create());

        $this->expectException(ValidationException::class);
        app(CsvImporter::class)->importAssets($workspace, $this->csv("name,contact,email,website,notes\nΠελάτης,,,,\n"), app(PlanGuard::class));
    }

    public function test_correct_files_still_import(): void
    {
        SystemCatalog::ensureAssetTypes();
        $workspace = app(WorkspaceProvisioner::class)->create('2bit', User::factory()->create());
        $imp = app(CsvImporter::class);

        $this->assertSame(1, $imp->importClients($workspace, $this->csv("name,contact,email,website,notes\nΠελάτης,,,,\n"), app(PlanGuard::class)));
        $this->assertSame(1, $imp->importAssets($workspace, $this->csv("name,type,client,expires_at,renewal_months\nexample.gr,domain,Πελάτης,2027-01-01,24\n"), app(PlanGuard::class)));
    }
}
