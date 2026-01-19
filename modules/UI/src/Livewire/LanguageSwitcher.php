<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

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
    public function changeLanguage(string $locale): void
    {
        if (! in_array($locale, ['en', 'id'])) {
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
