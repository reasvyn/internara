<?php

declare(strict_types=1);

use App\Modules\Setting\Domain\Locale\Livewire\LangSwitch;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cookie;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('52O1I: LangSwitch livewire component', function (): void {
    test('52O1I-FR-BRAND-016: mounts with current locale and renders options', function (): void {
        App::setLocale('en');

        Livewire::test(LangSwitch::class)
            ->assertSet('locale', 'en')
            ->assertSee('EN')
            ->assertSee(__('common.language.indonesian'))
            ->assertSee(__('common.language.english'));
    });

    test('52O1I-UC-BRAND-004: changes locale, sets cookie and session, and redirects safely', function (): void {
        $this->from('http://localhost/setup');

        Livewire::test(LangSwitch::class)
            ->call('changeLocale', 'id')
            ->assertSet('locale', 'id')
            ->assertDispatched('language-changed', locale: 'id')
            ->assertRedirect('http://localhost/setup');

        expect(Cookie::queued('locale')?->getValue())->toBe('id')
            ->and(App::getLocale())->toBe('id');
    });

    test('52O1I-FR-BRAND-016: ignores unsupported locale changes', function (): void {
        App::setLocale('en');

        Livewire::test(LangSwitch::class)
            ->call('changeLocale', 'fr')
            ->assertSet('locale', 'en');

        expect(App::getLocale())->toBe('en');
    });
});
