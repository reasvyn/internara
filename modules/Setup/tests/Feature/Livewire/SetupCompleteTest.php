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
use Modules\Setup\Services\SetupRequirementRegistry;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    app(SettingService::class)->setValue('setup_step_welcome', true);
    Gate::define('performStep', fn() => true);
    Gate::define('finalize', fn() => true);

    // Mock requirement providers
    $registry = app(SetupRequirementRegistry::class);
    foreach (['school', 'super-admin', 'department', 'internship'] as $identifier) {
        $registry->register(new class($identifier) implements SetupRequirementProvider {
            public function __construct(private string $id) {}
            public function getRequirementIdentifier(): string { return $this->id; }
            public function isSatisfied(): bool { return true; }
        });
    }
});

describe('SetupComplete Component', function () {
    test('it renders correctly with wizard layout', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_internship', true);

        $this->get(route('setup.complete', ['token' => 'test-token']));

        Livewire::test(SetupComplete::class)
            ->assertStatus(200);
    });

    test('it finalizes setup and redirects to landing when all checkboxes verified', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_internship', true);

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
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_internship', false);

        $this->get(route('setup.complete', ['token' => 'test-token']));

        Livewire::test(SetupComplete::class)->assertRedirect(route('setup.internship'));
    });
});