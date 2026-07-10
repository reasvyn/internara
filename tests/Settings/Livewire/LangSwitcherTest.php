<?php

declare(strict_types=1);

use App\Settings\Livewire\LangSwitcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    config(['app.locale' => 'en']);
});

test('lang switcher renders', function () {
    Livewire::test(LangSwitcher::class)
        ->assertSet('locale', 'en')
        ->assertViewIs('settings.livewire.lang-switcher');
});

test('lang switcher sets supported locale', function () {
    Livewire::test(LangSwitcher::class)
        ->call('setLocale', 'id')
        ->assertSet('locale', 'id')
        ->assertDispatched('language-changed');
});

test('lang switcher ignores unsupported locale', function () {
    Livewire::test(LangSwitcher::class)
        ->call('setLocale', 'fr')
        ->assertSet('locale', 'en');
});
