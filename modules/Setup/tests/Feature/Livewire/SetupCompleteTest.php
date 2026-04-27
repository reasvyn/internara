<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\School\Models\School;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\SetupComplete;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn() => true);
    Gate::define('finalize', fn() => true);
});

describe('SetupComplete Component', function () {
    test('it renders correctly', function () {
        app(SettingService::class)->setValue('setup_step_system', true);

        $this->get(route('setup.complete', ['token' => 'test-token']));

        Livewire::test(SetupComplete::class)
            ->assertStatus(200)
            ->assertSee(
                __('setup::wizard.complete.headline', ['app' => setting('app_name', 'Internara')]),
            );
    });

    test('it finalizes setup and redirects to landing', function () {
        app(SettingService::class)->setValue('setup_step_system', true);

        // Mock required data for finalization
        School::factory()->create(['name' => 'Test School']);

        $this->get(route('setup.complete', ['token' => 'test-token']));

        Livewire::test(SetupComplete::class)
            ->set('data_verified', true)
            ->set('security_aware', true)
            ->set('legal_agreed', true)
            ->call('nextStep')
            ->assertRedirect(route('login'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'system' not completed
        app(SettingService::class)->setValue('setup_step_system', false);

        $this->get(route('setup.complete', ['token' => 'test-token']));

        Livewire::test(SetupComplete::class)->assertRedirect(route('setup.system'));
    });
});
