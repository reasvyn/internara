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

describe('InternshipSetup Component', function () {
    test('it renders correctly with wizard layout', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_department', true);

        $this->get(route('setup.internship', ['token' => 'test-token']));

        Livewire::test(InternshipSetup::class)->assertStatus(200);
    });

    test('it proceeds to complete setup step when internship exists', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_department', true);

        // Required record 'internship' must exist
        Internship::factory()->create();

        $this->get(route('setup.internship', ['token' => 'test-token']));

        Livewire::test(InternshipSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.complete'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Prev step 'department' not completed
        app(SettingService::class)->setValue('setup_step_welcome', true);
        app(SettingService::class)->setValue('setup_step_department', false);

        $this->get(route('setup.internship', ['token' => 'test-token']));

        Livewire::test(InternshipSetup::class)->assertRedirect(route('setup.department'));
    });
});
