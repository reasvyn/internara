<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Admin\Services\Contracts\SuperAdminService;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Permission\Database\Seeders\PermissionSeeder;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\AccountSetup;
use Modules\Setup\Services\Contracts\SetupRequirementProvider;
use Modules\Setup\Services\SetupRequirementRegistry;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');
    $this->seed(PermissionSeeder::class);
    $this->seed(PermissionSeeder::class);

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    app(SettingService::class)->setValue('setup_step_welcome', true);
    Gate::define('performStep', fn () => true);

    // Mock requirement providers
    $registry = app(SetupRequirementRegistry::class);
    foreach (['school', 'super-admin', 'department', 'internship'] as $identifier) {
        $registry->register(
            new class($identifier) implements SetupRequirementProvider
            {
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

describe('AccountSetup Component', function () {
    test('it renders correctly with wizard layout', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_school', true);

        $this->get(route('setup.account', ['token' => 'test-token']));

        Livewire::test(AccountSetup::class)->assertStatus(200);
    });

    test(
        'it proceeds to department setup step when super admin exists and nextStep called',
        function () {
            app(SettingService::class)->setValue('setup_step_welcome', true);
            app(SettingService::class)->setValue('setup_step_school', true);

            // Required record 'super-admin' must exist for nextStep() to succeed
            $superAdmin = app(SuperAdminService::class)->create([
                'name' => 'Admin',
                'username' => 'admin',
                'email' => 'admin@example.com',
                'password' => 'password',
            ]);
            $superAdmin->assignRole('super-admin');

            $this->get(route('setup.account', ['token' => 'test-token']));

            Livewire::test(AccountSetup::class)
                ->call('nextStep')
                ->assertRedirect(route('setup.department'));
        },
    );

    test('it enforces setup sequence access control by redirecting', function () {
        // Prev step 'school' is NOT completed, should redirect to it
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_school', false);

        $this->get(route('setup.account', ['token' => 'test-token']));

        Livewire::test(AccountSetup::class)->assertRedirect(route('setup.school'));
    });
});
