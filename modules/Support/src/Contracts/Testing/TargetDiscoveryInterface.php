<?php

declare(strict_types=1);

namespace Modules\Support\Contracts\Testing;

/**
 * Interface for discovering testable targets within the modular monolith.
 *
 * S1 (Secure): Only discover enabled modules from trusted sources.
 * S2 (Sustain): Caching of discovery results for performance.
 * S3 (Scalable): Support for filtering and dirty detection at scale.
 */
interface TargetDiscoveryInterface
{
    /**
     * Identify all testable targets.
     *
     * @param array<string> $requestedModules
     * @param array<string> $missing Will be filled with modules not found
     * @return array<int, array{label: string, path: string, segments: array<string>}>
     */
    public function discover(array $requestedModules = [], bool $onlyDirty = false, array &$missing = []): array;

    /**
     * Check if a module is enabled and has test directories.
     */
    public function isTestable(string $moduleName): bool;

    /**
     * Get all enabled modules from the status file.
     *
     * @return array<string, bool>
     */
    public function getEnabledModules(): array;

    /**
     * Detect modules with uncommitted changes via git.
     *
     * @return array<string>
     */
    public function detectDirtyModules(): array;
}
