<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

test('domain:discover command runs successfully and performs all registration tasks', function () {
    $this->artisan('domain:discover')
        ->expectsOutputToContain(__('setup.cli.tasks.discover_livewire'))
        ->expectsOutputToContain(__('setup.cli.tasks.discover_policies'))
        ->expectsOutputToContain(__('setup.cli.tasks.discover_views'))
        ->expectsOutputToContain(__('setup.cli.tasks.discover_complete'))
        ->assertExitCode(0);
});
