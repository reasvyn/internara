<?php

declare(strict_types=1);

describe('YB7RG: auth structural contracts', function (): void {
    test('YB7RG-NFR-AUTH-019: every auth PHP file declares strict types', function (): void {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(base_path('app/Modules/Auth'), FilesystemIterator::SKIP_DOTS)
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

    test('YB7RG-NFR-AUTH-020: auth domain events fire from Actions, never from Livewire components', function (): void {
        $livewireDir = base_path('app/Modules/Auth/Domain/Login/Livewire');

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($livewireDir, FilesystemIterator::SKIP_DOTS)
        );

        $checked = 0;
        $offenders = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $checked++;
            $contents = (string) file_get_contents($file->getPathname());

            if (
                str_contains($contents, 'event(new ')
                || str_contains($contents, '::dispatch(')
                || str_contains($contents, '->dispatch(')
            ) {
                $offenders[] = $file->getPathname();
            }
        }

        expect($checked)->toBeGreaterThan(0)
            ->and($offenders)->toBe([]);

        // Positive control: the dispatches this test forbids in components
        // genuinely live in the Action, so an inversion would fail here.
        $action = (string) file_get_contents(
            base_path('app/Modules/Auth/Domain/Login/Actions/LoginAction.php')
        );

        expect($action)->toContain('event(new LoginFailed')
            ->and($action)->toContain('event(new LoginSucceeded');
    });

    test('YB7RG-NFR-AUTH-016: login, error, and lockout strings pass through the translation helper', function (): void {
        $action = (string) file_get_contents(
            base_path('app/Modules/Auth/Domain/Login/Actions/LoginAction.php')
        );
        $component = (string) file_get_contents(
            base_path('app/Modules/Auth/Domain/Login/Livewire/Login.php')
        );

        // Every rejection thrown by the Action resolves via __().
        $throws = preg_match_all('/throw new RejectedException\(/', $action);
        $translatedThrows = preg_match_all('/throw new RejectedException\(\s*__\(/', $action);

        expect($throws)->toBeGreaterThan(0)
            ->and($translatedThrows)->toBe($throws);

        // The known user-facing sentences never appear as literals.
        foreach (['These credentials do not match', 'has been blocked', 'Too many login attempts'] as $literal) {
            expect($action)->not->toContain($literal);
            expect($component)->not->toContain($literal);
        }

        // Component feedback resolves via __() or reuses the Action message.
        expect($component)->toContain("__('auth.throttle'")
            ->and($component)->toContain("__('auth.failed')")
            ->and($component)->toContain('$e->getMessage()');

        // The blade surface carries no hardcoded sentences (same shape as
        // the D3 scanner rule: quoted, uppercase-led, letter runs).
        $blade = (string) file_get_contents(base_path('resources/views/auth/login.blade.php'));

        $hardcoded = [];

        foreach (explode("\n", $blade) as $number => $line) {
            $stripped = trim($line);

            if (str_starts_with($stripped, '{{--')) {
                continue;
            }

            if (preg_match_all('/(?<![A-Za-z])[\'"]([A-Z][A-Za-z ]{3,})[\'"]/', $stripped, $matches)) {
                $hardcoded[] = ($number + 1).': '.implode(', ', $matches[1]);
            }
        }

        expect($hardcoded)->toBe([]);

        // The keys the surface resolves actually exist in both locales.
        foreach (['auth.failed', 'auth.blocked', 'auth.throttle'] as $key) {
            expect(__($key, [], 'en'))->not->toBe($key)
                ->and(__($key, [], 'id'))->not->toBe($key);
        }
    });
});
