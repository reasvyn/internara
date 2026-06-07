<?php

declare(strict_types=1);

use App\Livewire\ThemeSwitcher;

test('theme switcher renders', function () {
    Livewire::test(ThemeSwitcher::class)
        ->assertSet('theme', 'system')
        ->assertViewIs('livewire.theme-switcher');
});

test('theme switcher defaults to system', function () {
    Livewire::test(ThemeSwitcher::class)
        ->assertSet('theme', 'system');
});

test('theme switcher sets valid theme', function () {
    Livewire::test(ThemeSwitcher::class)
        ->call('setTheme', 'dark')
        ->assertSet('theme', 'dark')
        ->assertDispatched('theme-changed', theme: 'dark');
});

test('theme switcher sets light theme', function () {
    Livewire::test(ThemeSwitcher::class)
        ->call('setTheme', 'light')
        ->assertSet('theme', 'light')
        ->assertDispatched('theme-changed', theme: 'light');
});

test('theme switcher sets system theme', function () {
    Livewire::test(ThemeSwitcher::class)
        ->call('setTheme', 'system')
        ->assertSet('theme', 'system')
        ->assertDispatched('theme-changed', theme: 'system');
});

test('theme switcher ignores invalid theme', function () {
    Livewire::test(ThemeSwitcher::class)
        ->call('setTheme', 'invalid')
        ->assertSet('theme', 'system');
});
