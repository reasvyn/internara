<?php

declare(strict_types=1);

use App\Domain\Shared\Livewire\ThemeSwitcher;
use Livewire\Livewire;

describe('ThemeSwitcher', function () {
    it('renders successfully', function () {
        Livewire::test(ThemeSwitcher::class)
            ->assertStatus(200);
    });

    it('defaults to system theme', function () {
        Livewire::test(ThemeSwitcher::class)
            ->assertSet('theme', 'system');
    });

    it('sets light theme', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'light')
            ->assertSet('theme', 'light')
            ->assertDispatched('theme-changed');
    });

    it('sets dark theme', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'dark')
            ->assertSet('theme', 'dark')
            ->assertDispatched('theme-changed');
    });

    it('sets system theme', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'system')
            ->assertSet('theme', 'system')
            ->assertDispatched('theme-changed');
    });

    it('ignores invalid theme value', function () {
        Livewire::test(ThemeSwitcher::class)
            ->call('setTheme', 'invalid')
            ->assertSet('theme', 'system');
    });
});
