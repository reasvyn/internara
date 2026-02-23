<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Department\Services\Contracts\DepartmentService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\DepartmentSetup;
use Modules\Setup\Services\Contracts\SetupService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    app(SettingService::class)->setValue('setup_token', 'test-token');
    session(['setup_authorized' => true]);
});

describe('DepartmentSetup Component', function () {
    test('it renders correctly and contains the department manager slot', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_environment', true);
        app(SettingService::class)->setValue('setup_step_school', true);
        app(SettingService::class)->setValue('setup_step_account', true);

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(DepartmentSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.department.headline'));
    });

    test('it proceeds to internship setup step on next action', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_environment', true);
        app(SettingService::class)->setValue('setup_step_school', true);
        app(SettingService::class)->setValue('setup_step_account', true);

        // Required record 'department' must exist
        app(DepartmentService::class)->factory()->create();

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(DepartmentSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.internship'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'account' not completed
        Livewire::test(DepartmentSetup::class)
            ->assertRedirect(route('setup.account'));
    });

    test('it adheres to [SYRS-NF-401] with slot-based department UI', function () {
        app(SettingService::class)->setValue('setup_step_account', true);

        $viewPath = module_path('Setup', 'resources/views/livewire/department-setup.blade.php');
        $template = file_get_contents($viewPath);

        // Verify slot-based UI integration
        expect($template)->toContain('x-setup::layouts.setup-wizard')
            ->toContain("@slotRender('department-manager')")
            ->toContain('text-4xl');
    });
});
