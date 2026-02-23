<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\InternshipSetup;
use Modules\Setup\Services\Contracts\SetupService;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    app(SettingService::class)->setValue('setup_token', 'test-token');
    session(['setup_authorized' => true]);
});

describe('InternshipSetup Component', function () {
    test('it renders correctly and contains the internship manager slot', function () {
        app(SettingService::class)->setValue('setup_step_department', true);
        session(['setup_authorized' => true]);

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(InternshipSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.internship.headline'));
    });

    test('it proceeds to system setup step on next action', function () {
        app(SettingService::class)->setValue('setup_step_department', true);
        session(['setup_authorized' => true]);

        // Required record 'internship' must exist
        app(InternshipService::class)->factory()->create();

        Livewire::withQueryParams(['token' => 'test-token'])
            ->test(InternshipSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.system'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'department' not completed
        Livewire::test(InternshipSetup::class)
            ->assertRedirect(route('setup.department'));
    });

    test('it adheres to [SYRS-NF-401] with slot-based internship UI', function () {
        app(SettingService::class)->setValue('setup_step_department', true);

        $viewPath = module_path('Setup', 'resources/views/livewire/internship-setup.blade.php');
        $template = file_get_contents($viewPath);

        // Verify slot-based UI integration
        expect($template)->toContain('x-setup::layouts.setup-wizard')
            ->toContain("@slotRender('internship-manager')")
            ->toContain('text-4xl');
    });
});
