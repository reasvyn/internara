<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Support\Contracts\Testing\SessionManagerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Manages persistent test sessions with automated cleanup policies.
 *
 * S1 (Secure): Session data stored with integrity validation via hashing.
 * S2 (Sustain): Automated cleanup prevents disk space exhaustion.
 * S3 (Scalable): Support for multiple concurrent sessions with metadata tracking.
 */
class SessionManager implements SessionManagerInterface
{
    protected string $sessionPath;

    protected string $sessionId;

    protected const MAX_SESSION_AGE_DAYS = 30;

    protected const MAX_SESSIONS = 10;

    protected const SESSION_PRUNE_AGE_DAYS = 7;

    public function __construct(?string $sessionId = null)
    {
        $basePath = storage_path('framework/testing/sessions');
        $this->sessionId = $sessionId ?: $this->getLatestSessionId() ?: Str::uuid()->toString();
        $this->sessionPath = "{$basePath}/{$this->sessionId}";

        if (! File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0700, true);
        }

        if (! File::isDirectory($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0700, true);
        }

        // Automated cleanup on construction (S2: prevent disk exhaustion)
        $this->pruneOldSessions();
    }

    /**
     * Record the result of a test segment.
     *
     * @param array{output: string, errorOutput: string} $executionResult
     */
    public function record(
        string $module,
        string $type,
        bool $success,
        array $executionResult,
    ): void {
        $file = $this->getSegmentFile($module, $type);
        $modulePath = $this->resolveModulePath($module);

        $data = [
            'module' => $module,
            'type' => $type,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
            'integrity_hash' => $modulePath ? $this->calculateIntegrityHash($modulePath) : null,
            'peak_memory' => $executionResult['peakMemory'] ?? 0,
            'output' => $this->sanitizeOutput($executionResult['output'] ?? ''),
            'error' => $this->sanitizeOutput($executionResult['errorOutput'] ?? ''),
        ];

        File::put($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Check if a segment has passed and is still valid.
     */
    public function isPassed(string $module, string $type): bool
    {
        $file = $this->getSegmentFile($module, $type);

        if (! File::exists($file)) {
            return false;
        }

        $data = json_decode(File::get($file), true);

        if (! ($data['success'] ?? false)) {
            return false;
        }

        // Integrity Check: Compare stored hash with current state
        $modulePath = $this->resolveModulePath($module);
        if ($modulePath) {
            $currentHash = $this->calculateIntegrityHash($modulePath);
            if ($currentHash !== ($data['integrity_hash'] ?? null)) {
                return false; // Files have changed, invalidate PASS
            }
        }

        return true;
    }

    /**
     * Get all recorded results for the current session.
     *
     * @return array<int, array{module: string, type: string, success: bool, timestamp: string, peak_memory: int}>
     */
    public function getResults(): array
    {
        $files = File::files($this->sessionPath);
        $results = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $data = json_decode(File::get($file->getPathname()), true);
                if (is_array($data)) {
                    $results[] = $data;
                }
            }
        }

        // Sort by timestamp
        usort($results, fn ($a, $b) => ($a['timestamp'] ?? '') <=> ($b['timestamp'] ?? ''));

        return $results;
    }

    /**
     * Get the current session ID.
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * Clear the current session data.
     */
    public function clear(): void
    {
        if (File::isDirectory($this->sessionPath)) {
            File::deleteDirectory($this->sessionPath);
            File::makeDirectory($this->sessionPath, 0700, true);
        }
    }

    /**
     * Clear all sessions older than the specified days.
     */
    public function cleanup(int $olderThanDays = 7): int
    {
        $basePath = storage_path('framework/testing/sessions');
        if (! File::isDirectory($basePath)) {
            return 0;
        }

        $directories = File::directories($basePath);
        $cutoffTime = Carbon::now()->subDays($olderThanDays);
        $deletedCount = 0;

        foreach ($directories as $dir) {
            $lastModified = Carbon::createFromTimestamp(File::lastModified($dir));
            if ($lastModified->lt($cutoffTime)) {
                File::deleteDirectory($dir);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }

    /**
     * Get session metadata including disk usage.
     *
     * @return array{sessionId: string, segmentCount: int, diskUsageBytes: int, oldestTimestamp: ?string}
     */
    public function getMetadata(): array
    {
        $results = $this->getResults();
        $diskUsage = 0;

        if (File::isDirectory($this->sessionPath)) {
            $files = File::allFiles($this->sessionPath);
            foreach ($files as $file) {
                $diskUsage += $file->getSize();
            }
        }

        $oldestTimestamp = null;
        if (! empty($results)) {
            $timestamps = array_filter(array_column($results, 'timestamp'));
            if (! empty($timestamps)) {
                $oldestTimestamp = min($timestamps);
            }
        }

        return [
            'sessionId' => $this->sessionId,
            'segmentCount' => count($results),
            'diskUsageBytes' => $diskUsage,
            'oldestTimestamp' => $oldestTimestamp,
        ];
    }

    /**
     * Get all session IDs with their metadata.
     *
     * @return array<int, array{sessionId: string, segmentCount: int, diskUsageBytes: int, lastModified: string}>
     */
    public static function getAllSessions(): array
    {
        $basePath = storage_path('framework/testing/sessions');
        if (! File::isDirectory($basePath)) {
            return [];
        }

        $directories = File::directories($basePath);
        $sessions = [];

        foreach ($directories as $dir) {
            $sessionId = basename($dir);
            $session = new static($sessionId);
            $metadata = $session->getMetadata();
            $metadata['lastModified'] = Carbon::createFromTimestamp(
                File::lastModified($dir),
            )->toIso8601String();
            $sessions[] = $metadata;
        }

        // Sort by lastModified descending (newest first)
        usort($sessions, fn ($a, $b) => $b['lastModified'] <=> $a['lastModified']);

        return $sessions;
    }

    /**
     * Prune old sessions to prevent disk exhaustion (S2).
     */
    protected function pruneOldSessions(): void
    {
        $basePath = storage_path('framework/testing/sessions');
        if (! File::isDirectory($basePath)) {
            return;
        }

        $directories = File::directories($basePath);

        // If we have too many sessions, delete the oldest ones
        if (count($directories) > self::MAX_SESSIONS) {
            usort($directories, fn ($a, $b) => File::lastModified($a) <=> File::lastModified($b));

            $toDelete = count($directories) - self::MAX_SESSIONS;
            for ($i = 0; $i < $toDelete; $i++) {
                File::deleteDirectory($directories[$i]);
            }
        }

        // Also cleanup sessions older than MAX_SESSION_AGE_DAYS
        $this->cleanup(self::MAX_SESSION_AGE_DAYS);
    }

    /**
     * Calculate a lightweight integrity hash based on file modification times.
     */
    protected function calculateIntegrityHash(string $path): string
    {
        if (! is_dir($path)) {
            return File::exists($path) ? (string) File::lastModified($path) : '';
        }

        $lastMtime = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $mtime = $file->getMTime();
                if ($mtime > $lastMtime) {
                    $lastMtime = $mtime;
                }
            }
        }

        return (string) $lastMtime;
    }

    /**
     * Resolve the physical path for a module or system target.
     */
    protected function resolveModulePath(string $label): ?string
    {
        $labelLower = strtolower($label);

        if ($labelLower === 'system' || $labelLower === 'root') {
            return base_path('tests');
        }

        $path = base_path("modules/{$label}");

        return is_dir($path) ? $path : null;
    }

    /**
     * Generate a unique filename for a segment.
     */
    protected function getSegmentFile(string $module, string $type): string
    {
        $safeModule = Str::slug($module);
        $safeType = Str::slug($type);

        return "{$this->sessionPath}/{$safeModule}_{$safeType}.json";
    }

    /**
     * Identify the latest session ID from the file system.
     */
    protected function getLatestSessionId(): ?string
    {
        $basePath = storage_path('framework/testing/sessions');
        if (! File::isDirectory($basePath)) {
            return null;
        }

        $directories = File::directories($basePath);
        if (empty($directories)) {
            return null;
        }

        usort($directories, fn ($a, $b) => File::lastModified($b) <=> File::lastModified($a));

        return basename($directories[0]);
    }

    /**
     * Sanitize output to remove sensitive information (S1).
     */
    protected function sanitizeOutput(string $output): string
    {
        // Limit output size to prevent memory issues
        $maxLength = 10000;
        if (strlen($output) > $maxLength) {
            $output = substr($output, 0, $maxLength)."\n... [truncated]";
        }

        // Mask potential sensitive data patterns
        $patterns = [
            '/("password"\s*:\s*)"[^"]+"/i' => '$1"[masked]"',
            '/("token"\s*:\s*)"[^"]+"/i' => '$1"[masked]"',
            '/("secret"\s*:\s*)"[^"]+"/i' => '$1"[masked]"',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $output) ?? $output;
    }

    /**
     * Clear all session data (static method for commands).
     */
    public static function clearAll(): void
    {
        $basePath = storage_path('framework/testing/sessions');
        if (File::isDirectory($basePath)) {
            File::deleteDirectory($basePath);
        }
    }
}
