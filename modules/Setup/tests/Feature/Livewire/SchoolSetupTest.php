<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\School\Models\School;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\SchoolSetup;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn() => true);
});

describe('SchoolSetup Component', function () {
    test('it renders correctly and contains the school manager slot', function () {
        app(SettingService::class)->setValue('setup_step_environment', true);

        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.school.headline'));
    });

    test('it proceeds to account setup step upon school creation', function () {
        app(SettingService::class)->setValue('setup_step_environment', true);

        // Required record 'school' must exist
        School::factory()->create();

        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.account'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'environment' not completed
        app(SettingService::class)->setValue('setup_step_environment', false);

        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)->assertRedirect(route('setup.environment'));
    });

    test('it adheres to [SYRS-NF-401] with responsive layout', function () {
        app(SettingService::class)->setValue('setup_step_environment', true);

        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)->assertSeeHtml('text-4xl');
    });
});
