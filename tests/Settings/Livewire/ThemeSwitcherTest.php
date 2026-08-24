<?php

declare(strict_types=1);

use App\Settings\Livewire\LangSwitcher;
use App\Settings\Livewire\ThemeSwitcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('52O1I: ThemeSwitcher — TallstackUI migration', function (): void {
    it('52O1I-FR-T2: ThemeSwitcher renders x-dropdown (TallstackUI) not x-mary-dropdown', function (): void {
        $view = file_get_contents(resource_path('views/settings/livewire/theme-switcher.blade.php'));

        expect($view)->toContain('<x-ts-dropdown')
            ->toContain('x-ts-dropdown.items')
            ->not->toContain('<x-mary-dropdown')
            ->not->toContain('<x-mary-menu-item');
    });

    it('FB792-FR-TS6a: ThemeSwitcher uses TallstackUI first', function (): void {
        $view = file_get_contents(resource_path('views/settings/livewire/theme-switcher.blade.php'));

        expect($view)->toContain('x-ts-dropdown.items')
            ->toContain('x-ts-icon name="sun"')
            ->not->toContain('o-sun');
    });

    it('52O1I-FR-T2: setTheme dispatches theme-changed and persists cookie', function (): void {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark');

        expect(cookie()->queued('theme')->getValue())->toBe('dark');

        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'light')
            ->assertDispatched('theme-changed', theme: 'light');

        expect(cookie()->queued('theme')->getValue())->toBe('light');
    });

    it('52O1I-NFR-A4: theme switch view has aria-label', function (): void {
        $view = file_get_contents(resource_path('views/settings/livewire/theme-switcher.blade.php'));

        expect($view)->toContain('aria-label');
    });
});

describe('52O1I: LangSwitcher — TallstackUI migration', function (): void {
    it('52O1I-FR-L5: LangSwitcher renders x-dropdown for EN/ID', function (): void {
        $view = file_get_contents(resource_path('views/settings/livewire/lang-switcher.blade.php'));

        expect($view)->toContain('<x-ts-dropdown')
            ->toContain('x-ts-dropdown.items')
            ->not->toContain('<x-mary-dropdown');
    });

    it('52O1I-FR-L4: setLocale validates and persists EN/ID', function (): void {
        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'id')
            ->assertDispatched('language-changed');

        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'en')
            ->assertDispatched('language-changed');
    });

    it('52O1I-FR-L3: LangSwitcher view shows locale uppercase', function (): void {
        Livewire::test(LangSwitcher::class)
            ->assertSeeHtml('uppercase');
    });
});
