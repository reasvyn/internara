<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Manages persistent testing sessions for modular verification with integrity checks.
 *
 * This service enables long-running test suites to be executed across
 * multiple process lifetimes by persisting segment results to disk and
 * invalidating them if source files have changed.
 */
class TestSessionManager
{
    protected string $sessionPath;

    protected string $sessionId;

    public function __construct(?string $sessionId = null)
    {
        $this->sessionId = $sessionId ?: $this->getLatestSessionId() ?: Str::uuid()->toString();
        $basePath = storage_path('framework/testing/sessions');
        $this->sessionPath = "{$basePath}/{$this->sessionId}";

        if (! File::isDirectory($basePath)) {
            File::makeDirectory($basePath, 0700, true);
        }

        if (! File::isDirectory($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0700, true);
        }
    }

    /**
     * Record the result of a test segment with integrity metadata.
     */
    public function record(
        string $module,
        string $type,
        bool $success,
        string $output = '',
        string $error = '',
    ): void {
        $file = $this->getSegmentFile($module, $type);
        $modulePath = $this->resolveModulePath($module);

        $data = [
            'module' => $module,
            'type' => $type,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
            'integrity_hash' => $modulePath ? $this->calculateIntegrityHash($modulePath) : null,
            'output' => $output,
            'error' => $error,
        ];

        File::put($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Check if a segment has already passed and remains valid (no file changes).
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
            $mtime = $file->getMTime();
            if ($mtime > $lastMtime) {
                $lastMtime = $mtime;
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
     * Get all recorded results for the session.
     */
    public function getResults(): array
    {
        $files = File::files($this->sessionPath);
        $results = [];

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $results[] = json_decode(File::get($file->getPathname()), true);
            }
        }

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
     * Generate a unique filename for a segment.
     */
    protected function getSegmentFile(string $module, string $type): string
    {
        $safeModule = Str::slug($module);
        $safeType = Str::slug($type);

        return "{$this->sessionPath}/{$safeModule}_{$safeType}.json";
    }

    /**
     * Clear all session data.
     */
    public static function clearAll(): void
    {
        $basePath = storage_path('framework/testing/sessions');
        if (File::isDirectory($basePath)) {
            File::deleteDirectory($basePath);
        }
    }
}
