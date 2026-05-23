<?php

declare(strict_types=1);

namespace App\Domain\Shared\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class LangSwitcher extends Component
{
    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = app()->getLocale();
    }

    public function setLocale(string $locale): void
    {
        if (! in_array($locale, ['en', 'id'], true)) {
            return;
        }

        $this->locale = $locale;

        cookie()->queue(cookie()->forever('locale', $locale));
        app()->setLocale($locale);

        $this->dispatch('language-changed');
    }

    public function render(): View
    {
        return view('shared.lang-switcher');
    }
}
