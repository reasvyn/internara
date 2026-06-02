<?php

declare(strict_types=1);

use App\Domain\Shared\Livewire\LangSwitcher;
use App\Domain\Shared\Support\Locale;
use Illuminate\Support\Facades\App;
use Livewire\Livewire;

describe('LangSwitcher', function () {
    beforeEach(function () {
        App::setLocale('en');
    });

    it('mounts with current locale', function () {
        Livewire::test(LangSwitcher::class)
            ->assertSet('locale', 'en');
    });

    it('switches to Indonesian', function () {
        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'id')
            ->assertSet('locale', 'id')
            ->assertDispatched('language-changed');

        expect(App::getLocale())->toBe('id');
    });

    it('switches back to English', function () {
        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'id')
            ->call('setLocale', 'en')
            ->assertSet('locale', 'en');

        expect(App::getLocale())->toBe('en');
    });

    it('ignores unsupported locale', function () {
        Livewire::test(LangSwitcher::class)
            ->set('locale', 'en')
            ->call('setLocale', 'fr')
            ->assertSet('locale', 'en');

        expect(App::getLocale())->toBe('en');
    });

    it('queues locale cookie on change', function () {
        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'id');

        expect(Locale::current())->toBe('id');
    });

    it('renders correct view', function () {
        Livewire::test(LangSwitcher::class)
            ->assertViewIs('shared.lang-switcher');
    });
});
