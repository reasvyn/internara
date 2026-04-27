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

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn() => true);
});

describe('DepartmentSetup Component', function () {
    test('it renders correctly and contains the department manager slot', function () {
        app(SettingService::class)->setValue('setup_step_account', true);

        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.department.headline'));
    });

    test('it proceeds to internship setup step on next action', function () {
        app(SettingService::class)->setValue('setup_step_account', true);

        // Required record 'department' must exist
        Department::factory()->create();

        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.internship'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'account' not completed
        app(SettingService::class)->setValue('setup_step_account', false);

        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)->assertRedirect(route('setup.account'));
    });

    test('it adheres to [SYRS-NF-401] with responsive layout', function () {
        app(SettingService::class)->setValue('setup_step_account', true);

        $this->get(route('setup.department', ['token' => 'test-token']));

        Livewire::test(DepartmentSetup::class)->assertSeeHtml('text-4xl');
    });
});
