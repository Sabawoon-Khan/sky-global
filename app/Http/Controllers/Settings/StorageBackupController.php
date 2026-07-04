<?php

namespace App\Http\Controllers\Settings;

use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Http\Controllers\Concerns\AuthorizesMisPermissions;
use App\Http\Controllers\Controller;
use App\Jobs\RunBackupJob;
use App\Models\StorageBackup;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StorageBackupController extends Controller
{
    use AuthorizesMisPermissions;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, 'settings.edit');

        return Inertia::render('settings/Backups/Index', [
            'backups' => StorageBackup::query()
                ->with('createdBy:id,name')
                ->orderByDesc('created_at')
                ->limit(50)
                ->get()
                ->map(fn (StorageBackup $backup) => [
                    'id' => $backup->id,
                    'type' => $backup->type->value,
                    'filename' => $backup->filename,
                    'file_size' => $backup->file_size,
                    'status' => $backup->status->value,
                    'trigger' => $backup->trigger->value,
                    'error_message' => $backup->error_message,
                    'created_by' => $backup->createdBy?->name,
                    'download_url' => $backup->status === BackupStatus::Completed
                        ? route('settings.backups.download', $backup)
                        : null,
                    'started_at' => $backup->started_at?->toIso8601String(),
                    'completed_at' => $backup->completed_at?->toIso8601String(),
                    'created_at' => $backup->created_at?->toIso8601String(),
                ])
                ->values(),
            'backupEnabled' => (bool) config('backup.enabled'),
            'storageBackupEnabled' => (bool) config('backup.storage.enabled'),
            'databaseBackupEnabled' => (bool) config('backup.database.enabled'),
            'retentionDays' => (int) config('backup.retention_days', 30),
            'retentionCount' => (int) config('backup.retention_count', 10),
            'hasRemoteDisk' => filled(config('backup.remote_disk')),
        ]);
    }

    public function store(Request $request, BackupService $service): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        abort_unless(config('backup.enabled'), 422, 'Backups are disabled.');

        $validated = $request->validate([
            'type' => ['required', Rule::enum(BackupType::class)],
        ]);

        $type = BackupType::from($validated['type']);

        if ($type === BackupType::Storage && ! config('backup.storage.enabled')) {
            return back()->withErrors(['backup' => 'Storage backups are disabled.']);
        }

        if ($type === BackupType::Database && ! config('backup.database.enabled')) {
            return back()->withErrors(['backup' => 'Database backups are disabled.']);
        }

        $hasActiveBackup = StorageBackup::query()
            ->where('type', $type)
            ->whereIn('status', [BackupStatus::Pending, BackupStatus::Running])
            ->exists();

        if ($hasActiveBackup) {
            return back()->withErrors(['backup' => 'A backup of this type is already in progress.']);
        }

        $backup = $service->createPending($type, BackupTrigger::Manual, $request->user());

        RunBackupJob::dispatch($backup);

        $label = $type === BackupType::Storage ? 'Storage' : 'Database';

        return back()->with('success', "{$label} backup started.");
    }

    public function download(Request $request, StorageBackup $storageBackup): StreamedResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        abort_unless($storageBackup->status === BackupStatus::Completed, 404);
        abort_unless(Storage::disk($storageBackup->disk)->exists($storageBackup->path), 404);

        return Storage::disk($storageBackup->disk)->download(
            $storageBackup->path,
            $storageBackup->filename,
        );
    }

    public function destroy(Request $request, BackupService $service, StorageBackup $storageBackup): RedirectResponse
    {
        $this->authorizePermission($request, 'settings.edit');

        if (in_array($storageBackup->status, [BackupStatus::Pending, BackupStatus::Running], true)) {
            return back()->withErrors(['backup' => 'Cannot delete a backup that is still running.']);
        }

        $service->delete($storageBackup);

        return back()->with('success', 'Backup deleted.');
    }
}
