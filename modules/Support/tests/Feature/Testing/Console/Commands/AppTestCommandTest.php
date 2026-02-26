<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Feature\Testing\Console\Commands;

/**
 * Note: We avoid full execution of app:test within these tests to prevent
 * infinite recursion and long execution times. We mostly use --list mode
 * or verify basic identification logic.
 */
describe('AppTestCommand Orchestrator', function () {
    test('it can list test segments', function () {
        $this->artisan('app:test --list')
            ->expectsOutputToContain('Root')
            ->expectsOutputToContain('Shared')
            ->assertExitCode(0);
    });

    test('it can identify specific modules in list mode', function () {
        $this->artisan('app:test Shared Core --list')
            ->expectsOutputToContain('Shared')
            ->expectsOutputToContain('Core')
            ->assertExitCode(0);
    });

    test('it identifies invalid modules', function () {
        $this->artisan('app:test NonExistent --list')
            ->expectsOutputToContain('Target module [nonexistent] was not found')
            ->assertExitCode(1);
    });

    test('it filters segments correctly in list mode', function () {
        // Just verify it doesn't crash and returns success
        $this->artisan('app:test Root --unit-only --no-arch --list')
            ->assertExitCode(0);
    });
});
