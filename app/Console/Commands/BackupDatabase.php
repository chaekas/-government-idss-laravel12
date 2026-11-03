<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use Symfony\Component\Process\Process;
use Throwable;

class BackupDatabase extends Command
{
    protected $signature = 'idss:backup {--keep-days= : Number of days to retain generated backups}';

    protected $description = 'Create and verify a private database backup for the IDSS application';

    public function handle(): int
    {
        $directory = (string) config('idss.backups.path', storage_path('app/private/backups'));

        try {
            if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
                throw new \RuntimeException('Unable to create the private backup directory.');
            }

            @chmod($directory, 0700);

            if (!is_writable($directory)) {
                throw new \RuntimeException('The private backup directory is not writable.');
            }

            $driver = DB::connection()->getDriverName();
            $extension = match ($driver) {
                'sqlite' => 'sqlite',
                'pgsql' => 'dump',
                default => throw new \RuntimeException("Backups are not configured for the [{$driver}] database driver."),
            };

            $path = rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR
                .'idss-db-'.now()->format('Ymd_His_u').'.'.$extension;

            if ($driver === 'sqlite') {
                $this->backupSqlite($path);
            } else {
                $this->backupPostgres($path);
            }

            if (!is_file($path) || filesize($path) === 0) {
                throw new \RuntimeException('The database backup was not created or is empty.');
            }

            @chmod($path, 0600);
            $this->verifyBackup($path, $driver);
            $this->pruneOldBackups($directory);

            $this->info('Database backup created and verified: '.$path);

            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (isset($path) && is_file($path)) {
                @unlink($path);
            }

            Log::error('IDSS database backup failed.', [
                'driver' => config('database.default'),
                'exception' => $exception->getMessage(),
            ]);

            $this->error('Database backup failed. Check the application log and deployment backup configuration.');

            return self::FAILURE;
        }
    }

    private function backupSqlite(string $path): void
    {
        $connection = DB::connection();
        $source = $connection->getDatabaseName();

        if ($source === ':memory:' || !is_file($source)) {
            throw new \RuntimeException('A persistent SQLite database file is required; in-memory databases cannot be backed up.');
        }

        // VACUUM INTO creates a consistent SQLite snapshot, including committed WAL data.
        $connection->getPdo()->exec('VACUUM INTO '.$connection->getPdo()->quote($path));
    }

    private function backupPostgres(string $path): void
    {
        $database = config('database.connections.pgsql');

        if (empty($database['database']) || empty($database['username'])) {
            throw new \RuntimeException('PostgreSQL database name and username must be configured.');
        }

        $binary = (string) config('idss.backups.pg_dump_binary', 'pg_dump');
        $process = new Process([
            $binary,
            '--host='.(string) ($database['host'] ?? '127.0.0.1'),
            '--port='.(string) ($database['port'] ?? '5432'),
            '--username='.(string) $database['username'],
            '--format=custom',
            '--no-owner',
            '--no-acl',
            '--file='.$path,
            (string) $database['database'],
        ], base_path(), [
            'PGPASSWORD' => (string) ($database['password'] ?? ''),
        ]);

        $process->setTimeout(3600);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('pg_dump failed: '.trim($process->getErrorOutput()));
        }
    }

    private function verifyBackup(string $path, string $driver): void
    {
        if ($driver === 'sqlite') {
            $backup = new PDO('sqlite:'.$path);
            $result = $backup->query('PRAGMA integrity_check')->fetchColumn();
            $backup = null;

            if ($result !== 'ok') {
                throw new \RuntimeException('SQLite backup integrity check failed.');
            }

            return;
        }

        $binary = (string) config('idss.backups.pg_restore_binary', 'pg_restore');
        $process = new Process([$binary, '--list', $path], base_path());
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException('pg_restore could not read the generated PostgreSQL backup.');
        }
    }

    private function pruneOldBackups(string $directory): void
    {
        $keepDays = max(1, (int) ($this->option('keep-days') ?: config('idss.backups.keep_days', 30)));
        $cutoff = now()->subDays($keepDays)->getTimestamp();

        foreach (glob(rtrim($directory, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'idss-db-*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff && in_array(pathinfo($file, PATHINFO_EXTENSION), ['sqlite', 'dump'], true)) {
                @unlink($file);
            }
        }
    }
}
