<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Settings\Support\Locale;
use Illuminate\View\View;
use Livewire\Component;

class LangSwitcher extends Component
{
    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = Locale::current();
    }

    public function setLocale(string $locale): void
    {
        if (! Locale::isSupported($locale)) {
            return;
        }

        $this->locale = $locale;

        Locale::set($locale);

        $this->dispatch('language-changed');
    }

    public function render(): View
    {
        return view('livewire.lang-switcher');
    }
}
