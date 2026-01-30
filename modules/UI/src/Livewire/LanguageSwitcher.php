<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    /**
     * Get the supported locales from configuration.
     */
    #[Computed]
    public function locales(): array
    {
        return (array) config('ui.locales', []);
    }

    /**
     * Change the application locale.
     */
    public function changeLocale(string $locale): void
    {
        if (! array_key_exists($locale, $this->locales())) {
            return;
        }

        app()->setLocale($locale);
        session()->put('locale', $locale);

        $this->redirect(request()->header('Referer') ?: '/', navigate: true);
    }

    public function render()
    {
        return view('ui::livewire.language-switcher');
    }
}
