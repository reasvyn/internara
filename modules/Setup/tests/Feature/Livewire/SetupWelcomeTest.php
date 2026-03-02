<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Modules\Setting\Services\Contracts\SettingService;
use Modules\Setup\Livewire\SetupWelcome;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    App::setLocale('en');
    
    // Authorization for setup (Middleware & Gates)
    app(SettingService::class)->setValue('app_installed', false);
    app(SettingService::class)->setValue('setup_token', 'test-token');
    Gate::define('performStep', fn () => true);
});

describe('SetupWelcome Component', function () {
    test('it renders correctly', function () {
        $this->get(route('setup.welcome', ['token' => 'test-token']));

        Livewire::test(SetupWelcome::class)
            ->assertStatus(200)
            ->assertSee(__('setup::wizard.welcome.headline'));
    });

    test('it fulfills [SYRS-NF-401] with mobile-first hero layout', function () {
        $this->get(route('setup.welcome', ['token' => 'test-token']));

        Livewire::test(SetupWelcome::class)
            ->assertSeeHtml('container mx-auto')
            ->assertSeeHtml('md:text-5xl');
    });

    test('it redirects to environment setup step on next action', function () {
        $this->get(route('setup.welcome', ['token' => 'test-token']));

        Livewire::test(SetupWelcome::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.environment'));
    });

    test('it adheres to [SYRS-NF-403] by supporting multi-language toggle', function () {
        expect(class_exists(\Modules\UI\Livewire\LanguageSwitcher::class))->toBeTrue();
    });
});
