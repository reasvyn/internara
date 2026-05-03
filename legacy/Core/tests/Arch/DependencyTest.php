<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Arch;

/**
 * Architecture tests for Core module.
 *
 * Ensures Core maintains its architectural mandate:
 * - Only depends on Shared module
 * - No circular dependencies
 * - No cross-module model imports
 *
 * Uses filesystem operations only (no Laravel bootstrap needed).
 */
describe('Core Module Architecture', function () {
    $coreSrcPath = dirname(__DIR__, 2).'/src';

    test('ensures Core only depends on Shared module', function () use ($coreSrcPath) {
        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($coreSrcPath));

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace($coreSrcPath.'/', '', $file->getPathname());

            // Check for use statements importing non-Core, non-Shared modules
            preg_match_all(
                '/^use\s+Modules\\\\(?!Core\\\\|Shared\\\\)(\w+)\\\\/m',
                $content,
                $matches,
            );

            if (! empty($matches[1])) {
                foreach ($matches[1] as $module) {
                    if ($module !== 'UI' && $module !== 'Core' && $module !== 'Shared') {
                        $violations[] = "{$relativePath}: depends on Modules\\{$module}";
                    }
                }
            }

            // Specifically check UI dependency (critical violation)
            if (preg_match('/^use\s+Modules\\\\UI\\\\/m', $content)) {
                $violations[] = "{$relativePath}: CRITICAL - depends on UI module";
            }
        }

        expect($violations)->toBeEmpty(
            "Core module MUST only depend on Shared module.\nViolations:\n- ".
                implode("\n- ", $violations),
        );
    });

    test('ensures Core does not import models from other modules', function () use ($coreSrcPath) {
        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($coreSrcPath));

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // Check for cross-module model imports
            if (preg_match('/^use\s+Modules\\\\[A-Za-z]+\\\\Models\\\\/m', $content)) {
                $relativePath = str_replace($coreSrcPath.'/', '', $file->getPathname());
                $violations[] = $relativePath;
            }
        }

        expect($violations)->toBeEmpty(
            "Core module MUST NOT import models from other modules.\nViolations:\n- ".
                implode("\n- ", $violations),
        );
    });

    test('ensures all classes have strict_types declaration', function () use ($coreSrcPath) {
        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($coreSrcPath));

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $relativePath = str_replace($coreSrcPath.'/', '', $file->getPathname());

            if (! str_contains($content, 'declare(strict_types=1)')) {
                $violations[] = $relativePath;
            }
        }

        expect($violations)->toBeEmpty(
            "All PHP files MUST have declare(strict_types=1).\nViolations:\n- ".
                implode("\n- ", $violations),
        );
    });

    test('ensures helper classes in Support folders are final', function () use ($coreSrcPath) {
        $violations = [];

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($coreSrcPath));

        foreach ($iterator as $file) {
            if ($file->isDir() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($coreSrcPath.'/', '', $file->getPathname());

            // Check Support folders
            if (! str_contains($relativePath, 'Support/')) {
                continue;
            }

            $content = file_get_contents($file->getPathname());

            // Skip interfaces and abstract classes
            if (str_contains($content, 'interface ') || str_contains($content, 'abstract class ')) {
                continue;
            }

            if (! str_contains($content, 'final class ')) {
                $violations[] = $relativePath;
            }
        }

        expect($violations)->toBeEmpty(
            "Helper classes in Support folders MUST be declared final.\nViolations:\n- ".
                implode("\n- ", $violations),
        );
    });
});
