<?php

declare(strict_types=1);

use App\Livewire\LangSwitcher;
use Illuminate\Support\Facades\Cookie;

beforeEach(function () {
    config(['app.locale' => 'en']);
});

test('lang switcher renders', function () {
    Cookie::shouldReceive('get')->andReturn('en');

    Livewire::test(LangSwitcher::class)
        ->assertSet('locale', 'en')
        ->assertViewIs('livewire.lang-switcher');
});

test('lang switcher sets supported locale', function () {
    Cookie::shouldReceive('get')->andReturn('en');
    Cookie::shouldReceive('queue')->once();
    Cookie::shouldReceive('forever')->with('locale', 'id')->andReturnSelf();

    Livewire::test(LangSwitcher::class)
        ->call('setLocale', 'id')
        ->assertSet('locale', 'id')
        ->assertDispatched('language-changed');
});

test('lang switcher ignores unsupported locale', function () {
    Cookie::shouldReceive('get')->andReturn('en');

    Livewire::test(LangSwitcher::class)
        ->call('setLocale', 'fr')
        ->assertSet('locale', 'en');
});
