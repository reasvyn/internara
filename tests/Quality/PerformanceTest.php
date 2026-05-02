<?php

declare(strict_types=1);

namespace Tests\Quality;

use PHPUnit\Framework\TestCase;

/**
 * S3 - Scalable: Performance Tests
 * Ensures code doesn't have obvious performance issues
 */
class PerformanceTest extends TestCase
{
    /**
     * S3: Check for N+1 query problems in common patterns
     */
    public function test_no_n1_query_patterns(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app/Actions')
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $basename = $file->getBasename();

            // Check for potential N+1 patterns: looping through collection and accessing relationship
            if (preg_match('/foreach\s*\([^)]+\)\s*\{[^}]*->[^}]*->[^}]*\}/s', $content)) {
                // Simple heuristic: if we see a loop with relationship access
                if (preg_match('/\$[a-z]+\s*=\s*.*::all\(\)/', $content) ||
                    preg_match('/\$[a-z]+\s*=\s*.*::get\(\)/', $content)) {
                    $violations[] = $basename.': Potential N+1 query pattern detected (use eager loading with with())';
                }
            }
        }

        $this->assertEmpty($violations, "Potential N+1 queries found:\n".implode("\n", $violations));
    }

    /**
     * S3: Check for proper use of pagination instead of get()
     */
    public function test_controllers_use_pagination_for_lists(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app/Http/Controllers')
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $basename = $file->getBasename();

            // Check for index methods that return all records without pagination
            if (preg_match('/function\s+index\s*\([^)]*\)\s*\{[^}]*->get\(\)[^}]*\}/s', $content) ||
                preg_match('/function\s+index\s*\([^)]*\)\s*\{[^}]*::all\(\)[^}]*\}/s', $content)) {
                // Make sure it's not using pagination
                if (! preg_match('/->paginate\(/', $content) && ! preg_match('/->simplePaginate\(/', $content)) {
                    $violations[] = $basename.': Controller index method should use pagination (paginate/simplePaginate) instead of get()/all()';
                }
            }
        }

        $this->assertEmpty($violations, "Missing pagination in controllers:\n".implode("\n", $violations));
    }

    /**
     * S3: Check for proper query optimization with exists() instead of count()
     */
    public function test_use_exists_for_existence_checks(): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(dirname(__DIR__, 2).'/app')
        );

        $violations = [];

        foreach ($iterator as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            $basename = $file->getBasename();

            // Check for count() > 0 or count() == 0 patterns (should use exists())
            if (preg_match('/\->count\(\)\s*[><=!]=?\s*0/', $content) ||
                preg_match('/\->count\(\)\s*===?\s*0/', $content) ||
                preg_match('/\->count\(\)\s*!==?\s*0/', $content)) {
                $violations[] = $basename.': Use exists() or doesntExist() instead of count() > 0 for existence checks';
            }
        }

        $this->assertEmpty($violations, "Inefficient existence checks found:\n".implode("\n", $violations));
    }
}
