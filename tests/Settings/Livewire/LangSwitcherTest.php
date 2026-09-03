<?php

declare(strict_types=1);

use App\Modules\Settings\Domain\Locale\Support\Locale;
use App\Modules\Settings\Livewire\LangSwitcher;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

it('52O1I-FR-L5: changelocale persists the selected locale and dispatches language-changed', function (): void {
    App::setLocale('en');

    Livewire::test(LangSwitcher::class)
        ->dispatch('lang-switch', 'id')
        ->assertDispatched('language-changed');

    expect(App::getLocale())->toBe('id');
});

it('52O1I-FR-L4: changeLocale ignores unsupported locales', function (): void {
    App::setLocale('en');

    Livewire::test(LangSwitcher::class)
        ->dispatch('lang-switch', 'xx')
        ->assertNotDispatched('language-changed');

    expect(App::getLocale())->toBe('en');
    expect(Locale::current())->toBe('en');
});
