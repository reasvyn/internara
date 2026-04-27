<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Internship\Models\Internship;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\InternshipSetup;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn() => true);
});

describe('InternshipSetup Component', function () {
    test('it renders correctly and contains the internship manager slot', function () {
        app(SettingService::class)->setValue('setup_step_department', true);

        $this->get(route('setup.internship', ['token' => 'test-token']));

        Livewire::test(InternshipSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.internship.headline'));
    });

    test('it proceeds to system setup step on next action', function () {
        app(SettingService::class)->setValue('setup_step_department', true);

        // Required record 'internship' must exist
        Internship::factory()->create();

        $this->get(route('setup.internship', ['token' => 'test-token']));

        Livewire::test(InternshipSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.system'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'department' not completed
        app(SettingService::class)->setValue('setup_step_department', false);

        $this->get(route('setup.internship', ['token' => 'test-token']));

        Livewire::test(InternshipSetup::class)->assertRedirect(route('setup.department'));
    });

    test('it adheres to [SYRS-NF-401] with responsive layout', function () {
        app(SettingService::class)->setValue('setup_step_department', true);

        $this->get(route('setup.internship', ['token' => 'test-token']));

        Livewire::test(InternshipSetup::class)->assertSeeHtml('text-4xl');
    });
});
