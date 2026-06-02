<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Console;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('DomainDiscoverCommand', function () {
    it('runs successfully', function () {
        $this->artisan('domain:discover')
            ->assertSuccessful();
    });

    it('discovers livewire components', function () {
        $this->artisan('domain:discover')
            ->expectsOutputToContain(__('setup.cli.tasks.discover_livewire'));
    });

    it('discovers policies', function () {
        $this->artisan('domain:discover')
            ->expectsOutputToContain(__('setup.cli.tasks.discover_policies'));
    });

    it('registers view namespaces', function () {
        $this->artisan('domain:discover')
            ->expectsOutputToContain(__('setup.cli.tasks.discover_views'));
    });

    it('outputs discovery complete message', function () {
        $this->artisan('domain:discover')
            ->expectsOutputToContain(__('setup.cli.tasks.discover_complete'));
    });
});
