<?php

declare(strict_types=1);

test('it can list test segments', function () {
    // We expect at least 'Root' and 'Shared'
    $this->artisan('app:test --list')
        ->expectsOutputToContain('Root')
        ->expectsOutputToContain('Shared')
        ->assertExitCode(0);
});

test('it can filter tests by module', function () {
    // Only running Shared module tests
    $this->artisan('app:test Shared')
        ->expectsOutputToContain('Testing segment: Shared > Unit')
        ->assertExitCode(0);
});

test('it identifies invalid modules', function () {
    $this->artisan('app:test NonExistent')
        ->expectsOutputToContain('Test Execution Summary')
        ->assertExitCode(0);
});
