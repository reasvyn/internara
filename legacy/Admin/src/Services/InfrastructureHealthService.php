<?php

declare(strict_types=1);

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Admin\Services\Contracts\InfrastructureHealthService as Contract;

/**
 * Class InfrastructureHealthService
 *
 * Implements infrastructure monitoring by querying low-level system tables
 * and configuration settings. Results are cached to ensure high dashboard performance.
 */
class InfrastructureHealthService implements Contract
{
    /**
     * {@inheritdoc}
     */
    public function getQueueStatus(): array
    {
        return Cache::remember('infra:queue_status', now()->addMinutes(1), function () {
            return [
                'pending' => DB::table('jobs')->count(),
                'failed' => DB::table('failed_jobs')->count(),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getDatabaseSize(): string
    {
        return Cache::remember('infra:db_size', now()->addHour(), function () {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            try {
                if ($driver === 'sqlite') {
                    $path = config("database.connections.{$connection}.database");
                    if (file_exists($path)) {
                        return $this->formatBytes(filesize($path));
                    }
                }

                if ($driver === 'mysql') {
                    $dbName = config("database.connections.{$connection}.database");
                    $res = DB::select(
                        'SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = ?',
                        [$dbName],
                    );

                    return $this->formatBytes((int) ($res[0]->size ?? 0));
                }
            } catch (\Exception $e) {
                return 'Unknown';
            }

            return 'N/A';
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveSessionCount(): int
    {
        return (int) Cache::remember('infra:active_sessions', now()->addMinutes(5), function () {
            return DB::table('sessions')
                ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
                ->count();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getLastBackupTimestamp(): ?string
    {
        return (string) setting('last_successful_backup_at');
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1 << 10 * $pow;

        return round($bytes, $precision).' '.$units[$pow];
    }
}
