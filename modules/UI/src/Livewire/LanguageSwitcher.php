<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

class LanguageSwitcher extends Component
{
    /**
     * The supported locales.
     */
    public array $locales = [
        'en' => ['name' => 'English', 'flag' => '🇬🇧'],
        'id' => ['name' => 'Indonesia', 'flag' => '🇮🇩'],
    ];

    /**
     * Change the application locale.
     */
    public function changeLocale(string $locale): void
    {
        if (! array_key_exists($locale, $this->locales)) {
            return;
        }

        Session::put('locale', $locale);
        App::setLocale($locale);

        $this->dispatch('locale_changed', locale: $locale);

        // Refresh page to apply changes across the application
        $this->redirect(request()->header('Referer') ?: route('dashboard'));
    }

    public function render()
    {
        return view('ui::livewire.language-switcher');
    }
}
