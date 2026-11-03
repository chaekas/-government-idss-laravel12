<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PDO;
use Tests\TestCase;

class BackupCommandTest extends TestCase
{
    public function test_sqlite_backup_command_creates_a_private_integrity_checked_snapshot(): void
    {
        $source = storage_path('framework/testing/idss-backup-source-'.uniqid('', true).'.sqlite');
        $backupDirectory = storage_path('framework/testing/idss-backups-'.uniqid('', true));
        File::ensureDirectoryExists(dirname($source));
        touch($source);

        $originalDefault = config('database.default');
        $originalSqlite = config('database.connections.sqlite.database');
        $originalBackupPath = config('idss.backups.path');

        try {
            config([
                'database.default' => 'sqlite',
                'database.connections.sqlite.database' => $source,
                'idss.backups.path' => $backupDirectory,
            ]);
            DB::purge('sqlite');

            Schema::connection('sqlite')->create('backup_probe', function (Blueprint $table) {
                $table->id();
                $table->string('value');
            });
            DB::connection('sqlite')->table('backup_probe')->insert(['value' => 'preserved']);

            $this->artisan('idss:backup')->assertExitCode(0);

            $files = glob($backupDirectory.DIRECTORY_SEPARATOR.'idss-db-*.sqlite') ?: [];
            $this->assertCount(1, $files);

            $backup = new PDO('sqlite:'.$files[0]);
            $this->assertSame('ok', $backup->query('PRAGMA integrity_check')->fetchColumn());
            $this->assertSame('preserved', $backup->query('SELECT value FROM backup_probe')->fetchColumn());
            $backup = null;

            clearstatcache(true, $files[0]);
            $this->assertSame(0600, fileperms($files[0]) & 0777);
        } finally {
            DB::purge('sqlite');
            config([
                'database.default' => $originalDefault,
                'database.connections.sqlite.database' => $originalSqlite,
                'idss.backups.path' => $originalBackupPath,
            ]);
            @unlink($source);
            File::deleteDirectory($backupDirectory);
        }
    }
}
