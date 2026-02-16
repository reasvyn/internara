<?php

declare(strict_types=1);

namespace Modules\Support\Tests\Feature\Testing\Console\Commands;

test('it can list test segments', function () {
    $this->artisan('app:test --list')
        ->expectsOutputToContain('Root')
        ->expectsOutputToContain('Shared')
        ->assertExitCode(0);
});

test('it can filter tests by module', function () {
    $this->artisan('app:test Shared --no-browser')
        ->expectsOutputToContain('Testing segment')
        ->expectsOutputToContain('Shared')
        ->expectsOutputToContain('Unit')
        ->assertExitCode(0);
});

test('it identifies invalid modules', function () {
    $this->artisan('app:test NonExistent')
        ->expectsOutputToContain('Test Execution Summary')
        ->assertExitCode(0);
});
