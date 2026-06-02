<?php

declare(strict_types=1);

use App\Domain\Shared\Livewire\ThemeSwitcher;
use Livewire\Livewire;

describe('ThemeSwitcher', function () {
    it('mounts with system theme by default', function () {
        Livewire::test(ThemeSwitcher::class)
            ->assertSet('theme', 'system');
    });

    it('switches to light theme', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'light')
            ->assertSet('theme', 'light')
            ->assertDispatched('theme-changed');
    });

    it('switches to dark theme', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'dark')
            ->assertSet('theme', 'dark')
            ->assertDispatched('theme-changed');
    });

    it('switches to system theme', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'system')
            ->assertSet('theme', 'system')
            ->assertDispatched('theme-changed');
    });

    it('ignores invalid theme value', function () {
        Livewire::test(ThemeSwitcher::class)
            ->set('theme', 'light')
            ->call('setTheme', 'invalid')
            ->assertSet('theme', 'light');
    });

    it('queues theme cookie on change', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'light');

        expect(cookie()->hasQueued('theme'))->toBeTrue();
    });

    it('renders correct view', function () {
        Livewire::test(ThemeSwitcher::class)
            ->assertViewIs('shared.theme-switcher');
    });
});
