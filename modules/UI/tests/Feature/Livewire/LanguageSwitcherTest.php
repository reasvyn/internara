<?php

declare(strict_types=1);

namespace Modules\UI\Tests\Feature\Livewire;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Modules\UI\Livewire\LanguageSwitcher;

test('it can change application locale', function () {
    App::setLocale('en');

    Livewire::test(LanguageSwitcher::class)->call('changeLocale', 'id')->assertRedirect('/');

    expect(App::getLocale())->toBe('id');
    expect(Session::get('locale'))->toBe('id');
});

test('it ignores unsupported locales', function () {
    App::setLocale('en');

    Livewire::test(LanguageSwitcher::class)->call('changeLocale', 'fr')->assertNoRedirect();

    expect(App::getLocale())->toBe('en');
});
