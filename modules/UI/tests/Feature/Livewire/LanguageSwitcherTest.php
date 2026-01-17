<?php

declare(strict_types=1);

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Modules\UI\Livewire\LanguageSwitcher;

test('it can change application locale', function () {
    // Initial locale should be default (id or en)
    App::setLocale('en');

    Livewire::test(LanguageSwitcher::class)
        ->call('changeLocale', 'id')
        ->assertDispatched('locale_changed', locale: 'id');

    expect(Session::get('locale'))->toBe('id');
});

test('it ignores unsupported locales', function () {
    App::setLocale('en');

    Livewire::test(LanguageSwitcher::class)
        ->call('changeLocale', 'fr') // French is not supported
        ->assertNotDispatched('locale_changed');

    expect(Session::get('locale'))->not->toBe('fr');
});
