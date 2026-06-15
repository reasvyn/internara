<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Support;

class BackupRunner
{
    private string $backupDir;

    private string $timestamp;

    public function __construct()
    {
        $this->backupDir = storage_path('app/backup');
        $this->timestamp = now()->format('Y-m-d_His');
    }

    public function runDatabaseDump(): string
    {
        $driver = config('database.default');
        $filename = "backup_database_{$this->timestamp}.sql.gz";
        $path = "{$this->backupDir}/{$filename}";

        if (! is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $command = match ($driver) {
            'mysql' => $this->mysqlDumpCommand($path),
            'pgsql' => $this->pgDumpCommand($path),
            'sqlite' => $this->sqliteCopyCommand($path),
            default => throw new \RuntimeException("Unsupported database driver: {$driver}"),
        };

        $this->execute($command);

        return $path;
    }

    public function runStorageDump(): string
    {
        $filename = "backup_storage_{$this->timestamp}.tar.gz";
        $path = "{$this->backupDir}/{$filename}";

        if (! is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }

        $storagePath = storage_path('app');
        $publicPath = storage_path('app/public');

        if (! is_dir($publicPath)) {
            throw new \RuntimeException('Storage directory not found.');
        }

        $command = sprintf(
            'tar -czf %s -C %s public 2>/dev/null',
            escapeshellarg($path),
            escapeshellarg($storagePath),
        );

        $this->execute($command);

        return $path;
    }

    public function runCombinedDump(): string
    {
        $dbPath = $this->runDatabaseDump();
        $storagePath = $this->runStorageDump();

        $filename = "backup_both_{$this->timestamp}.tar.gz";
        $combinedPath = "{$this->backupDir}/{$filename}";

        $command = sprintf(
            'tar -czf %s -C %s %s -C %s %s 2>/dev/null',
            escapeshellarg($combinedPath),
            escapeshellarg(dirname($dbPath)),
            escapeshellarg(basename($dbPath)),
            escapeshellarg(dirname($storagePath)),
            escapeshellarg(basename($storagePath)),
        );

        $this->execute($command);

        unlink($dbPath);
        unlink($storagePath);

        return $combinedPath;
    }

    public function deleteFile(string $path): bool
    {
        if (file_exists($path) && str_starts_with(realpath($path), realpath($this->backupDir))) {
            return unlink($path);
        }

        return false;
    }

    public function fileSize(string $path): int
    {
        return file_exists($path) ? filesize($path) : 0;
    }

    private function mysqlDumpCommand(string $path): string
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $configFile = tempnam(sys_get_temp_dir(), 'my_cnf_');
        $configContent = "[client]\nuser={$user}\npassword={$pass}\nhost={$host}\nport={$port}\n";
        file_put_contents($configFile, $configContent);
        chmod($configFile, 0600);

        return sprintf(
            'mysqldump --defaults-extra-file=%s --single-transaction --routines --skip-lock-tables %s 2>/dev/null | gzip > %s',
            escapeshellarg($configFile),
            escapeshellarg($db),
            escapeshellarg($path),
        );
    }

    private function pgDumpCommand(string $path): string
    {
        $host = config('database.connections.pgsql.host');
        $port = config('database.connections.pgsql.port');
        $db = config('database.connections.pgsql.database');
        $user = config('database.connections.pgsql.username');
        $pass = config('database.connections.pgsql.password');

        $passFile = tempnam(sys_get_temp_dir(), 'pgpass_');
        $passContent = "{$host}:{$port}:{$db}:{$user}:{$pass}\n";
        file_put_contents($passFile, $passContent);
        chmod($passFile, 0600);

        return sprintf(
            'PGPASSFILE=%s pg_dump --host=%s --port=%s --username=%s --no-password --format=c %s 2>/dev/null | gzip > %s',
            escapeshellarg($passFile),
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            escapeshellarg($db),
            escapeshellarg($path),
        );
    }

    private function sqliteCopyCommand(string $path): string
    {
        $dbPath = database_path(
            config('database.connections.sqlite.database', 'database.sqlite'),
        );

        $gzPath = $path.'.gz';

        return sprintf('cp %s %s && gzip -f %s', escapeshellarg($dbPath), escapeshellarg($path), escapeshellarg($path));
    }

    private function execute(string $command): void
    {
        $output = [];
        $returnCode = 0;

        exec($command.' 2>&1', $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \RuntimeException(implode("\n", $output));
        }
    }
}
