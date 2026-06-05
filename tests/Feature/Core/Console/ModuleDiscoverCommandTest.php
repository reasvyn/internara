<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

test('module:discover runs successfully and performs all registration tasks', function () {
    $this->artisan('module:discover')
        ->expectsOutputToContain(__('setup.cli.tasks.discover_livewire'))
        ->expectsOutputToContain(__('setup.cli.tasks.discover_policies'))
        ->expectsOutputToContain(__('setup.cli.tasks.discover_views'))
        ->expectsOutputToContain(__('setup.cli.tasks.discover_complete'))
        ->assertExitCode(0);
});
