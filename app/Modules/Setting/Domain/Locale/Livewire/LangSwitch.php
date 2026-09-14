<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Locale\Livewire;

use App\Modules\Setting\Domain\Locale\Support\Locale;
use Illuminate\View\View;
use Livewire\Component;

class LangSwitch extends Component
{
    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = Locale::current();
    }

    public function changeLocale(string $locale): void
    {
        if (! Locale::isSupported($locale)) {
            return;
        }

        $this->locale = $locale;

        Locale::set($locale);

        $this->dispatch('language-changed');

        $this->redirect(request()->fullUrl(), navigate: true);
    }

    public function render(): View
    {
        return view('setting.livewire.lang-switch');
    }
}
