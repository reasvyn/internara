<?php

declare(strict_types=1);

namespace Modules\Support\Testing\Support;

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Responsible for identifying testable targets within the modular monolith.
 */
class TargetDiscovery
{
    /**
     * Identifies all testable targets based on input and environment state.
     *
     * @param array<string> $requestedModules
     * @param array<string> $missing Reference parameter for missing modules.
     */
    public function identify(array $requestedModules, bool $onlyDirty, array &$missing = []): array
    {
        $targets = [];
        $foundRequested = [];
        $missing = [];

        $dirtyModules = $onlyDirty ? $this->getDirtyModules() : null;

        // 1. Consolidated System Level Target (Previously System & Root)
        if (File::isDirectory(base_path('tests'))) {
            if ($this->shouldInclude('system', $requestedModules, $dirtyModules)) {
                $targets[] = [
                    'label' => 'System',
                    'path' => base_path('tests'),
                    'segments' => ['Arch', 'Unit', 'Feature', 'Browser'],
                ];
                $foundRequested[] = 'system';
            }
        }

        // 2. Modular Targets
        $statusPath = base_path('modules_statuses.json');
        if (File::exists($statusPath)) {
            $statuses = json_decode(File::get($statusPath), true) ?: [];
            foreach ($statuses as $moduleName => $isActive) {
                if ($isActive !== true) {
                    continue;
                }

                $lowerName = strtolower($moduleName);
                if ($this->shouldInclude($lowerName, $requestedModules, $dirtyModules)) {
                    $foundRequested[] = $lowerName;
                    $testPath = base_path("modules/{$moduleName}/tests");
                    if (File::isDirectory($testPath)) {
                        $targets[] = [
                            'label' => $moduleName,
                            'path' => $testPath,
                            'segments' => ['Arch', 'Unit', 'Feature', 'Browser'],
                        ];
                    }
                }
            }
        }

        if (! empty($requestedModules)) {
            $missing = array_diff($requestedModules, $foundRequested);
        }

        return $targets;
    }

    /**
     * Logic to determine if a target should be included in the test run.
     */
    protected function shouldInclude(string $label, array $requested, ?array $dirty): bool
    {
        $label = strtolower($label);

        if (! empty($requested)) {
            return in_array($label, $requested);
        }

        if ($dirty !== null) {
            return in_array($label, $dirty);
        }

        return true;
    }

    /**
     * Detects changed modules using git status.
     *
     * @return array<string>
     */
    protected function getDirtyModules(): array
    {
        $process = new Process(['git', 'status', '--porcelain'], base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            return [];
        }

        $output = $process->getOutput();
        $changedModules = [];

        foreach (explode("\n", $output) as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            $file = substr($line, 3);
            if (str_starts_with($file, 'modules/')) {
                $parts = explode('/', $file);
                if (isset($parts[1])) {
                    $changedModules[] = strtolower($parts[1]);
                }
            } else {
                $changedModules[] = 'system';
            }
        }

        return array_unique($changedModules);
    }
}
