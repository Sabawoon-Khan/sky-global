<?php

namespace Tests\Feature;

use App\Models\Archive\ArchivedDocument;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ArchiveDocumentExpiryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');

        Storage::fake('local');
    }

    public function test_store_persists_optional_expires_at(): void
    {
        $file = UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf');

        $this->actingAs($this->owner)
            ->post(route('archive.store'), [
                'title' => 'Contract renewal',
                'direction' => 'incoming',
                'expires_at' => '2026-12-31',
                'file' => $file,
            ])
            ->assertRedirect(route('archive.index'));

        $document = ArchivedDocument::query()->first();

        $this->assertNotNull($document);
        $this->assertSame('2026-12-31', $document->expires_at?->toDateString());
        $this->assertFalse($document->is_expired);
    }

    public function test_past_expires_at_marks_document_expired_on_show(): void
    {
        $document = ArchivedDocument::query()->create([
            'reference_number' => 'ARC-TEST-001',
            'title' => 'Old permit',
            'direction' => 'internal',
            'file_path' => 'archive/test.pdf',
            'expires_at' => now()->subDay()->toDateString(),
            'uploaded_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('archive.show', $document))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('mis/archive/Show')
                ->where('document.is_expired', true)
                ->where('document.is_expiring_soon', false)
            );
    }

    public function test_future_expires_at_within_thirty_days_is_expiring_soon(): void
    {
        $document = ArchivedDocument::query()->create([
            'reference_number' => 'ARC-TEST-002',
            'title' => 'Renewal notice',
            'direction' => 'outgoing',
            'file_path' => 'archive/test2.pdf',
            'expires_at' => now()->addDays(10)->toDateString(),
            'uploaded_by' => $this->owner->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('archive.show', $document))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('document.is_expired', false)
                ->where('document.is_expiring_soon', true)
            );
    }

    public function test_store_rejects_invalid_expires_at(): void
    {
        $file = UploadedFile::fake()->create('letter.pdf', 100, 'application/pdf');

        $this->actingAs($this->owner)
            ->post(route('archive.store'), [
                'title' => 'Bad date doc',
                'direction' => 'incoming',
                'expires_at' => 'not-a-date',
                'file' => $file,
            ])
            ->assertSessionHasErrors('expires_at');
    }
}
