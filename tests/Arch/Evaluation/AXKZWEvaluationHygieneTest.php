<?php

declare(strict_types=1);

describe('AXKZW evaluation hygiene', function (): void {
    test('AXKZW-NFR-EVAL-008: every evaluation PHP file declares strict types', function (): void {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app/Modules/Evaluation'), FilesystemIterator::SKIP_DOTS)
        );

        $checked = 0;
        $offenders = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $checked++;

            if (! str_contains((string) file_get_contents($file->getPathname()), 'declare(strict_types=1);')) {
                $offenders[] = $file->getPathname();
            }
        }

        expect($checked)->toBeGreaterThan(0)
            ->and($offenders)->toBe([]);
    });
});
