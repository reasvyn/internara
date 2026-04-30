<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Department\Models\Department;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Services\SetupRequirementRegistry;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    app(SettingService::class)->setValue('setup_step_welcome', true);
    Gate::define('performStep', fn() => true);

    // Mock requirement providers
    $registry = app(SetupRequirementRegistry::class);
    foreach (['school', 'super-admin', 'department', 'internship'] as $identifier) {
        $registry->register(
            new class ($identifier) implements SetupRequirementProvider {
                public function __construct(private string $id) {}
                public function getRequirementIdentifier(): string
                {
                    return $this->id;
                }
                public function isSatisfied(): bool
                {
                    return true;
                }
            },
        );
    }
});

describe('DepartmentSetup Component', function () {
    test('it renders correctly with wizard layout', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_account', true);

        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)->assertStatus(200);
    });

    test('it proceeds to internship setup step when department exists', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_account', true);

        // Required record 'department' must exist
        Department::factory()->create();

        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.internship'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Prev step 'account' not completed
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_account', false);

        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)->assertRedirect(route('setup.account'));
    });
});
