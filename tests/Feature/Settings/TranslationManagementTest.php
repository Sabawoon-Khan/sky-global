<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use App\Services\TranslationFileService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private string $tempLangPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');

        $this->tempLangPath = storage_path('framework/testing/lang-'.uniqid());
        mkdir($this->tempLangPath, 0777, true);

        file_put_contents($this->tempLangPath.'/en.json', json_encode([
            'Hello' => 'Hello',
            'Goodbye' => 'Goodbye',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

        file_put_contents($this->tempLangPath.'/fa.json', json_encode([
            'Hello' => 'سلام',
            'Goodbye' => 'Goodbye',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

        file_put_contents($this->tempLangPath.'/ps.json', json_encode([
            'Hello' => 'سلام',
            'Goodbye' => 'Goodbye',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");

        $this->app->singleton(TranslationFileService::class, fn () => new TranslationFileService($this->tempLangPath));
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempLangPath)) {
            array_map('unlink', glob($this->tempLangPath.'/*') ?: []);
            rmdir($this->tempLangPath);
        }

        parent::tearDown();
    }

    public function test_owner_can_view_translations_index(): void
    {
        $this->actingAs($this->owner)
            ->get(route('settings.translations.index'))
            ->assertOk();
    }

    public function test_viewer_cannot_access_translations_index(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole('Viewer');

        $this->actingAs($viewer)
            ->get(route('settings.translations.index'))
            ->assertForbidden();
    }

    public function test_owner_can_update_translation_values_without_changing_keys(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.translations.update'), [
                'locale' => 'fa',
                'translations' => [
                    'Hello' => 'درود',
                ],
            ])
            ->assertRedirect();

        $updated = json_decode((string) file_get_contents($this->tempLangPath.'/fa.json'), true);

        $this->assertSame('درود', $updated['Hello']);
        $this->assertArrayHasKey('Goodbye', $updated);
        $this->assertArrayNotHasKey('درود', $updated);
    }

    public function test_unknown_translation_keys_are_rejected(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.translations.update'), [
                'locale' => 'fa',
                'translations' => [
                    'Unknown key' => 'Value',
                ],
            ])
            ->assertSessionHasErrors('translations');
    }
}
