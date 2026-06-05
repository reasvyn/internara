<?php

declare(strict_types=1);

use App\Core\Livewire\LangSwitcher;
use App\Core\Livewire\ThemeSwitcher;
use App\Core\Support\Locale;
use Livewire\Livewire;

test('LangSwitcher initializes with current locale and changes locale successfully', function () {
    $current = Locale::current();
    $target = $current === 'en' ? 'id' : 'en';

    Livewire::test(LangSwitcher::class)
        ->assertSet('locale', $current)
        ->call('setLocale', $target)
        ->assertSet('locale', $target)
        ->assertDispatched('language-changed');
});

test('LangSwitcher ignores unsupported locales', function () {
    $current = Locale::current();

    Livewire::test(LangSwitcher::class)
        ->assertSet('locale', $current)
        ->call('setLocale', 'unsupported_lang')
        ->assertSet('locale', $current)
        ->assertNotDispatched('language-changed');
});

test('ThemeSwitcher initializes and updates theme preference', function () {
    Livewire::test(ThemeSwitcher::class)
        ->assertSet('theme', 'system')
        ->call('setTheme', 'dark')
        ->assertSet('theme', 'dark')
        ->assertDispatched('theme-changed', theme: 'dark');
});

test('ThemeSwitcher ignores invalid themes', function () {
    Livewire::test(ThemeSwitcher::class)
        ->assertSet('theme', 'system')
        ->call('setTheme', 'invalid-theme-name')
        ->assertSet('theme', 'system')
        ->assertNotDispatched('theme-changed');
});
