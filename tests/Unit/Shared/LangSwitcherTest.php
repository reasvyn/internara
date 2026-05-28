<?php

declare(strict_types=1);

use App\Domain\Shared\Livewire\LangSwitcher;
use Livewire\Livewire;

describe('LangSwitcher', function () {
    beforeEach(function () {
        config(['app.locale' => 'en']);
    });

    it('renders successfully', function () {
        Livewire::test(LangSwitcher::class)
            ->assertStatus(200);
    });

    it('sets english locale', function () {
        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'en')
            ->assertSet('locale', 'en')
            ->assertDispatched('language-changed');
    });

    it('sets indonesian locale', function () {
        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'id')
            ->assertSet('locale', 'id')
            ->assertDispatched('language-changed');
    });

    it('ignores unsupported locale', function () {
        Livewire::test(LangSwitcher::class)
            ->call('setLocale', 'fr')
            ->assertSet('locale', 'en');
    });
});
