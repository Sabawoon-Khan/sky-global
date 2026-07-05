<?php

namespace App\Services;

use App\Enums\BackupStatus;
use App\Enums\BackupTrigger;
use App\Enums\BackupType;
use App\Models\StorageBackup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use ZipArchive;

class BackupService
{
    public function createPending(BackupType $type, BackupTrigger $trigger, ?User $user = null): StorageBackup
    {
        $filename = $this->filenameFor($type);
        $path = $filename;

        return StorageBackup::query()->create([
            'type' => $type,
            'filename' => $filename,
            'path' => $path,
            'disk' => config('backup.disk', 'backups'),
            'status' => BackupStatus::Pending,
            'trigger' => $trigger,
            'created_by' => $user?->id,
        ]);
    }

    public function run(StorageBackup $backup): StorageBackup
    {
        $backup->update([
            'status' => BackupStatus::Running,
            'started_at' => now(),
            'error_message' => null,
        ]);

        try {
            $disk = Storage::disk($backup->disk);
            $absolutePath = $disk->path($backup->path);

            match ($backup->type) {
                BackupType::Storage => $this->createStorageArchive(
                    $absolutePath,
                    config('backup.storage.paths', []),
                ),
                BackupType::Database => $this->createDatabaseBackup($absolutePath),
            };

            $fileSize = is_file($absolutePath) ? filesize($absolutePath) : null;

            $backup->update([
                'status' => BackupStatus::Completed,
                'file_size' => $fileSize !== false ? $fileSize : null,
                'completed_at' => now(),
            ]);

            $this->copyToRemoteDisk($backup);
            $this->applyRetention($backup->type);
        } catch (\Throwable $exception) {
            Log::error('Backup failed', [
                'backup_id' => $backup->id,
                'type' => $backup->type->value,
                'message' => $exception->getMessage(),
            ]);

            if (Storage::disk($backup->disk)->exists($backup->path)) {
                Storage::disk($backup->disk)->delete($backup->path);
            }

            $backup->update([
                'status' => BackupStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }

        return $backup->fresh();
    }

    public function delete(StorageBackup $backup): void
    {
        if (Storage::disk($backup->disk)->exists($backup->path)) {
            Storage::disk($backup->disk)->delete($backup->path);
        }

        $remoteDisk = config('backup.remote_disk');

        if ($remoteDisk && Storage::disk($remoteDisk)->exists($backup->path)) {
            Storage::disk($remoteDisk)->delete($backup->path);
        }

        $backup->delete();
    }

    public function applyRetention(BackupType $type): void
    {
        $retentionDays = (int) config('backup.retention_days', 30);
        $retentionCount = (int) config('backup.retention_count', 10);

        $expiredBackups = StorageBackup::query()
            ->where('type', $type)
            ->where('status', BackupStatus::Completed)
            ->where('created_at', '<', now()->subDays($retentionDays))
            ->get();

        foreach ($expiredBackups as $backup) {
            $this->delete($backup);
        }

        $completedBackups = StorageBackup::query()
            ->where('type', $type)
            ->where('status', BackupStatus::Completed)
            ->orderByDesc('created_at')
            ->get();

        if ($completedBackups->count() <= $retentionCount) {
            return;
        }

        $completedBackups
            ->slice($retentionCount)
            ->each(fn (StorageBackup $backup) => $this->delete($backup));
    }

    private function filenameFor(BackupType $type): string
    {
        $timestamp = now()->format('Y-m-d-His');

        return match ($type) {
            BackupType::Storage => "storage-{$timestamp}.zip",
            BackupType::Database => sprintf(
                'database-%s.%s',
                $timestamp,
                $this->databaseBackupExtension(),
            ),
        };
    }

    private function databaseBackupExtension(): string
    {
        return $this->databaseDriver() === 'sqlite' ? 'sqlite' : 'sql';
    }

    private function databaseConnectionName(): string
    {
        return config('backup.database.connection') ?: config('database.default');
    }

    private function databaseDriver(): string
    {
        return (string) config('database.connections.'.$this->databaseConnectionName().'.driver');
    }

    /**
     * @param  list<string>  $sourcePaths
     */
    private function createStorageArchive(string $destinationPath, array $sourcePaths): void
    {
        $this->ensureDirectoryExists(dirname($destinationPath));

        $zip = new ZipArchive;

        if ($zip->open($destinationPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create storage backup archive.');
        }

        foreach ($sourcePaths as $sourcePath) {
            if (! is_dir($sourcePath)) {
                continue;
            }

            $this->addDirectoryToZip($zip, $sourcePath, basename($sourcePath));
        }

        if ($zip->close() !== true) {
            throw new RuntimeException('Could not finalize storage backup archive.');
        }
    }

    private function createDatabaseBackup(string $destinationPath): void
    {
        $this->ensureDirectoryExists(dirname($destinationPath));

        match ($this->databaseDriver()) {
            'sqlite' => $this->backupSqliteDatabase($destinationPath),
            'mysql', 'mariadb' => $this->backupMysqlDatabase($destinationPath),
            'pgsql' => $this->backupPostgresDatabase($destinationPath),
            default => throw new RuntimeException('Unsupported database driver for backup: '.$this->databaseDriver()),
        };
    }

    private function backupSqliteDatabase(string $destinationPath): void
    {
        $connection = $this->databaseConnectionName();
        $databasePath = config("database.connections.{$connection}.database");

        if (! is_string($databasePath) || $databasePath === '') {
            throw new RuntimeException('SQLite database path is not configured.');
        }

        if ($databasePath === ':memory:') {
            $this->exportInMemorySqlite($connection, $destinationPath);

            return;
        }

        if (! is_file($databasePath)) {
            throw new RuntimeException('SQLite database file does not exist.');
        }

        if (! copy($databasePath, $destinationPath)) {
            throw new RuntimeException('Could not copy SQLite database file.');
        }
    }

    private function exportInMemorySqlite(string $connection, string $destinationPath): void
    {
        $source = DB::connection($connection);
        $destination = new \PDO('sqlite:'.$destinationPath);
        $destination->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $destination->exec('PRAGMA foreign_keys=OFF');

        $tables = $source->select(
            "SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' AND sql IS NOT NULL ORDER BY name",
        );

        foreach ($tables as $table) {
            $destination->exec((string) $table->sql);

            $tableName = (string) $table->name;
            $rows = $source->table($tableName)->get();

            if ($rows->isEmpty()) {
                continue;
            }

            $columns = array_keys((array) $rows->first());
            $columnList = '"'.implode('","', $columns).'"';
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $statement = $destination->prepare(
                "INSERT INTO \"{$tableName}\" ({$columnList}) VALUES ({$placeholders})",
            );

            foreach ($rows as $row) {
                $statement->execute(array_values((array) $row));
            }
        }

        $destination->exec('PRAGMA foreign_keys=ON');
    }

    private function backupMysqlDatabase(string $destinationPath): void
    {
        $connection = config('database.connections.'.$this->databaseConnectionName());

        $process = Process::env([
            'MYSQL_PWD' => (string) ($connection['password'] ?? ''),
        ])->run([
            'mysqldump',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.($connection['port'] ?? '3306'),
            '--user='.($connection['username'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--result-file='.$destinationPath,
            (string) ($connection['database'] ?? ''),
        ]);

        if (! $process->successful()) {
            throw new RuntimeException(trim($process->errorOutput()) ?: 'MySQL database backup failed.');
        }
    }

    private function backupPostgresDatabase(string $destinationPath): void
    {
        $connection = config('database.connections.'.$this->databaseConnectionName());

        $process = Process::env([
            'PGPASSWORD' => (string) ($connection['password'] ?? ''),
        ])->run([
            'pg_dump',
            '--host='.($connection['host'] ?? '127.0.0.1'),
            '--port='.($connection['port'] ?? '5432'),
            '--username='.($connection['username'] ?? 'root'),
            '--file='.$destinationPath,
            '--format=plain',
            '--no-owner',
            '--no-privileges',
            (string) ($connection['database'] ?? ''),
        ]);

        if (! $process->successful()) {
            throw new RuntimeException(trim($process->errorOutput()) ?: 'PostgreSQL database backup failed.');
        }
    }

    private function addDirectoryToZip(ZipArchive $zip, string $directory, string $zipPrefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            $relativePath = $zipPrefix.'/'.substr($file->getPathname(), strlen($directory) + 1);

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);

                continue;
            }

            $zip->addFile($file->getPathname(), $relativePath);
        }
    }

    private function copyToRemoteDisk(StorageBackup $backup): void
    {
        $remoteDisk = config('backup.remote_disk');

        if (! $remoteDisk || ! Storage::disk($backup->disk)->exists($backup->path)) {
            return;
        }

        $stream = Storage::disk($backup->disk)->readStream($backup->path);

        if ($stream === null) {
            throw new RuntimeException('Could not read backup file for remote upload.');
        }

        Storage::disk($remoteDisk)->writeStream($backup->path, $stream);

        if (is_resource($stream)) {
            fclose($stream);
        }
    }

    private function ensureDirectoryExists(string $directory): void
    {
        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Could not create backup directory.');
        }
    }
}
