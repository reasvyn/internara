<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;
use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\SetupComplete;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Gate::define("performStep", fn () => true);
    Gate::define("finalize", fn () => true);
});

beforeEach(function () {
    app(SettingService::class)->setValue('setup_token', 'test-token');
    session(['setup_authorized' => true]);
});

describe('SetupComplete Component', function () {
    test('it renders correctly', function () {
        app(SettingService::class)->setValue('setup_step_system', true);

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(SetupComplete::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.complete.headline', ['app' => 'Internara']));
    });

    test('it finalizes setup and redirects to landing', function () {
        app(SettingService::class)->setValue('setup_step_system', true);

        // Mock school record for finalization logic
        app(SchoolService::class)
            ->factory()
            ->create(['name' => 'Test School']);

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(SetupComplete::class)
            ->call('nextStep')
            ->assertRedirect(route('login'));

        expect(setting('app_installed'))->toBeTrue();
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'system' not completed
        Livewire::test(SetupComplete::class)->assertRedirect(route('setup.system'));
    });
});
