<?php

declare(strict_types=1);

namespace App\Modules\Settings\Livewire;

use App\Modules\Settings\Domain\Locale\Support\Locale;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class LangSwitcher extends Component
{
    public string $locale = 'en';

    public function mount(): void
    {
        $this->locale = Locale::current();
    }

    #[On('lang-switch')]
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
        return view('settings.livewire.lang-switcher');
    }
}
