<?php

declare(strict_types=1);

namespace Modules\Setup\Tests\Feature\Livewire;
use Illuminate\Support\Facades\Gate;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;
use Modules\Setup\Livewire\SetupWelcome;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Gate::define('performStep', fn () => true);
    Gate::define('finalize', fn () => true);
});

describe('SetupWelcome Component', function () {
    test('it renders correctly', function () {
        Livewire::test(SetupWelcome::class)
            ->assertStatus(200)
            ->assertViewIs('setup::livewire.setup-welcome')
            ->assertSee(__('setup::wizard.welcome.headline'))
            ->assertSee(__('setup::wizard.welcome.cta'));
    });

    test('it fulfills [SYRS-NF-401] with mobile-first and design system structure', function () {
        $viewPath = module_path('Setup', 'resources/views/livewire/setup-welcome.blade.php');
        $template = file_get_contents($viewPath);

        $rendered = Blade::render($template, [
            'app_name' => 'Internara',
        ]);

        // Verify Tailwind v4 responsive classes and structural integrity
        expect($rendered)
            ->toContain('container mx-auto')
            ->toContain('grid grid-cols-1')
            ->toContain('md:grid-cols-3')
            ->toContain('text-4xl');
    });

    test('it redirects to environment setup step on next action', function () {
        Livewire::test(SetupWelcome::class)
            ->call('nextStep')
            ->assertRedirect(route('setup.environment'));
    });

    test('it adheres to [SYRS-NF-403] by supporting localization', function () {
        app()->setLocale('id');

        Livewire::test(SetupWelcome::class)->assertSee(__('setup::wizard.welcome.headline'));

        app()->setLocale('en');

        Livewire::test(SetupWelcome::class)->assertSee(__('setup::wizard.welcome.headline'));
    });
});
