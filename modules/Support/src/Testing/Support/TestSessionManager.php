<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Manages persistent testing sessions for modular verification.
 *
 * This service enables long-running test suites to be executed across
 * multiple process lifetimes by persisting segment results to disk.
 */
class TestSessionManager
{
    protected string $sessionPath;

    protected string $sessionId;

    public function __construct(?string $sessionId = null)
    {
        $this->sessionId = $sessionId ?: $this->getLatestSessionId() ?: Str::uuid()->toString();
        $this->sessionPath = storage_path("framework/testing/sessions/{$this->sessionId}");
        
        if (!File::isDirectory($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0755, true);
        }
    }

    /**
     * Record the result of a test segment.
     */
    public function record(string $module, string $type, bool $success, string $output = '', string $error = ''): void
    {
        $file = $this->getSegmentFile($module, $type);
        
        $data = [
            'module' => $module,
            'type' => $type,
            'success' => $success,
            'timestamp' => now()->toIso8601String(),
            'output' => $output,
            'error' => $error,
        ];

        File::put($file, json_encode($data, JSON_PRETTY_PRINT));
    }

    /**
     * Check if a segment has already passed in the current session.
     */
    public function isPassed(string $module, string $type): bool
    {
        $file = $this->getSegmentFile($module, $type);
        
        if (!File::exists($file)) {
            return false;
        }

        $data = json_decode(File::get($file), true);
        return (bool) ($data['success'] ?? false);
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
        if (!File::isDirectory($basePath)) {
            return null;
        }

        $directories = File::directories($basePath);
        if (empty($directories)) {
            return null;
        }

        // Sort by modification time to get the newest
        usort($directories, fn($a, $b) => File::lastModified($b) <=> File::lastModified($a));

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
