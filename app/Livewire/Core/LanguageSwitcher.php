<?php

declare(strict_types=1);

namespace App\Livewire\Core;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

/**
 * Language Switcher Component
 *
 * S2 - Sustain: Simple language toggle with session storage and instant UI refresh.
 */
class LanguageSwitcher extends Component
{
    /**
     * The current application locale.
     */
    public string $currentLocale;

    /**
     * Map of supported locales to their display names.
     */
    public array $supportedLocales = [
        'en' => 'English',
        'id' => 'Indonesia',
    ];

    /**
     * Initialize the component with the current locale.
     */
    public function mount(): void
    {
        $this->currentLocale = App::getLocale();
    }

    /**
     * Switch the application language.
     */
    public function switchLanguage(string $locale): void
    {
        if (! array_key_exists($locale, $this->supportedLocales)) {
            return;
        }

        Session::put('locale', $locale);
        App::setLocale($locale);
        $this->currentLocale = $locale;

        // Dispatch events for components that need manual refreshing
        $this->dispatch('language-changed', locale: $locale);

        // Fully refresh the page to ensure all translations (including static ones) are updated
        $this->redirect(request()->header('Referer', '/'));
    }

    /**
     * Render the component view.
     */
    public function render()
    {
        return view('livewire.core.language-switcher');
    }
}
