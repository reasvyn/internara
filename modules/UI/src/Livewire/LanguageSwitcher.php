<?php

declare(strict_types=1);

namespace Modules\UI\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\UI\Services\Contracts\LocalizationService;

class LanguageSwitcher extends Component
{
    /**
     * Get the supported locales from the localization service.
     */
    #[Computed]
    public function locales(): array
    {
        return app(LocalizationService::class)->getSupportedLocales();
    }

    /**
     * Change the application locale.
     */
    public function changeLocale(string $locale, LocalizationService $service): void
    {
        if ($service->setLocale($locale)) {
            $this->js('window.location.reload()');
        }
    }

    public function render()
    {
        return view('ui::livewire.language-switcher');
    }
}
