<?php

declare(strict_types=1);

namespace App\SysAdmin\Backups\Services;

class BackupRunner
{
    private string $backupDir;

    private string $timestamp;

    /** @var string[] Paths to temp files that contain credentials */
    private array $tempFiles = [];

    public function __construct()
    {
        $this->backupDir = storage_path('app/backup');
        $this->timestamp = now()->format('Y-m-d_His');
    }

    public function __destruct()
    {
        $this->cleanupTempFiles();
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

        try {
            $this->execute($command);
        } finally {
            $this->cleanupTempFiles();
        }

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

    private function createTempFile(string $prefix, string $content): string
    {
        $file = tempnam(sys_get_temp_dir(), $prefix);
        file_put_contents($file, $content);
        chmod($file, 0600);
        $this->tempFiles[] = $file;

        return $file;
    }

    private function cleanupTempFiles(): void
    {
        foreach ($this->tempFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
        $this->tempFiles = [];
    }

    private function mysqlDumpCommand(string $path): string
    {
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');
        $db = config('database.connections.mysql.database');
        $user = config('database.connections.mysql.username');
        $pass = config('database.connections.mysql.password');

        $configFile = $this->createTempFile('my_cnf_', "[client]\nuser={$user}\npassword={$pass}\nhost={$host}\nport={$port}\n");

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

        $passFile = $this->createTempFile('pgpass_', "{$host}:{$port}:{$db}:{$user}:{$pass}\n");

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
