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
use Modules\Setup\Services\Contracts\SetupRequirementProvider;
use Modules\Setup\Services\SetupRequirementRegistry;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');

    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
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

describe('SchoolSetup Component', function () {
    test('it renders correctly with wizard layout', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);

        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)->assertStatus(200);
    });

    test('it proceeds to account setup step upon school creation', function () {
        app(SettingService::class)->setValue('setup_step_welcome', true);

        // Required record 'school' must exist
        School::factory()->create();

        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.account'));
    });

    test('it enforces setup sequence access control by redirecting', function () {
        // Step 'welcome' not completed
        app(SettingService::class)->setValue('setup_step_welcome', false);

        $this->get(route('setup.school', ['token' => 'test-token']));

        Livewire::test(SchoolSetup::class)->assertRedirect(route('setup.welcome'));
    });
});
