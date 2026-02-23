<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\SchoolSetup;
use Modules\Setup\Services\Contracts\SetupService;

uses(LazilyRefreshDatabase::class);

describe('SchoolSetup Component', function () {
    test('it renders correctly and contains the school manager slot', function () {
        app(SettingService::class)->setValue('setup_step_environment', true);

        Livewire::test(SchoolSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.school.headline'));
    });

    test('it proceeds to account setup step upon school_saved event', function () {
        app(SettingService::class)->setValue('setup_step_environment', true);

        // Required record 'school' must exist to proceed
        app(SchoolService::class)->factory()->create();

        Livewire::test(SchoolSetup::class)
            ->dispatch('school_saved')
            ->assertRedirect(route('setup.account'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'environment' not completed, should redirect to it (as it is the prevStep)
        Livewire::test(SchoolSetup::class)
            ->assertRedirect(route('setup.environment'));
    });

    test('it adheres to [SYRS-NF-401] with slot-based UI integration', function () {
        app(SettingService::class)->setValue('setup_step_environment', true);

        $viewPath = module_path('Setup', 'resources/views/livewire/school-setup.blade.php');
        $template = file_get_contents($viewPath);

        // Verify slot injection and responsive layout components
        expect($template)->toContain('x-setup::layouts.setup-wizard')
            ->toContain("@slotRender('school-manager')")
            ->toContain('text-4xl');
    });
});
