<?php

namespace Tests\Feature;

use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Models\StorageBackup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class StorageBackupTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('Owner');
    }

    private function fakeBackupsDisk(): void
    {
        Storage::fake('backups');
    }

    public function test_owner_can_view_backups_index(): void
    {
        $this->actingAs($this->owner)
            ->get(route('settings.backups.index'))
            ->assertOk();
    }

    public function test_owner_can_trigger_manual_storage_backup(): void
    {
        Queue::fake();

        $this->actingAs($this->owner)
            ->post(route('settings.backups.store'), ['type' => 'storage'])
            ->assertRedirect();

        $this->assertDatabaseHas('storage_backups', [
            'type' => BackupType::Storage->value,
            'status' => BackupStatus::Pending->value,
            'trigger' => BackupTrigger::Manual->value,
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_owner_can_trigger_manual_database_backup(): void
    {
        Queue::fake();

        $this->actingAs($this->owner)
            ->post(route('settings.backups.store'), ['type' => 'database'])
            ->assertRedirect();

        $this->assertDatabaseHas('storage_backups', [
            'type' => BackupType::Database->value,
            'status' => BackupStatus::Pending->value,
            'trigger' => BackupTrigger::Manual->value,
            'created_by' => $this->owner->id,
        ]);
    }

    public function test_storage_backup_command_creates_zip_archive(): void
    {
        $this->fakeBackupsDisk();

        Storage::disk('local')->put('sample.txt', 'backup-me');

        $this->artisan('storage:backup')->assertSuccessful();

        $backup = StorageBackup::query()->where('type', BackupType::Storage)->first();

        $this->assertNotNull($backup);
        $this->assertSame(BackupStatus::Completed, $backup->status);
        $this->assertTrue(Storage::disk('backups')->exists($backup->path));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open(Storage::disk('backups')->path($backup->path)) === true);
        $this->assertNotFalse($zip->locateName('private/sample.txt'));
        $zip->close();
    }

    public function test_database_backup_command_creates_sqlite_snapshot(): void
    {
        $this->artisan('database:backup');

        $backup = StorageBackup::query()->where('type', BackupType::Database)->first();

        $this->assertNotNull($backup);
        $this->assertSame(
            BackupStatus::Completed,
            $backup->status,
            $backup->error_message ?? 'Database backup did not complete.',
        );
        $this->assertTrue(Storage::disk('backups')->exists($backup->path));
        $this->assertStringEndsWith('.sqlite', $backup->filename);
        $this->assertGreaterThan(0, $backup->file_size);

        Storage::disk('backups')->delete($backup->path);
        $backup->delete();
    }

    public function test_owner_can_download_completed_backup(): void
    {
        $this->fakeBackupsDisk();
        Storage::disk('backups')->put('storage-test.zip', 'zip-content');

        $backup = StorageBackup::query()->create([
            'type' => BackupType::Storage,
            'filename' => 'storage-test.zip',
            'path' => 'storage-test.zip',
            'disk' => 'backups',
            'file_size' => 11,
            'status' => BackupStatus::Completed,
            'trigger' => BackupTrigger::Manual,
            'created_by' => $this->owner->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get(route('settings.backups.download', $backup))
            ->assertOk()
            ->assertDownload('storage-test.zip');
    }

    public function test_owner_can_delete_completed_backup(): void
    {
        $this->fakeBackupsDisk();
        Storage::disk('backups')->put('storage-delete.zip', 'zip-content');

        $backup = StorageBackup::query()->create([
            'type' => BackupType::Storage,
            'filename' => 'storage-delete.zip',
            'path' => 'storage-delete.zip',
            'disk' => 'backups',
            'file_size' => 11,
            'status' => BackupStatus::Completed,
            'trigger' => BackupTrigger::Manual,
            'created_by' => $this->owner->id,
            'completed_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->delete(route('settings.backups.destroy', $backup))
            ->assertRedirect();

        $this->assertDatabaseMissing('storage_backups', ['id' => $backup->id]);
        $this->assertFalse(Storage::disk('backups')->exists('storage-delete.zip'));
    }
}
