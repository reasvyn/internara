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

        $this->dispatch('language-changed', locale: $locale);

        $targetUrl = request()->header('referer');
        if (! $targetUrl || str_contains($targetUrl, '/livewire/update')) {
            $targetUrl = url()->previous();
        }
        if (! $targetUrl || str_contains($targetUrl, '/livewire/update')) {
            $targetUrl = route('home');
        }

        $this->redirect($targetUrl, navigate: false);
    }

    /**
     * @return array<string, array{code: string, label: string, short: string, flag: string}>
     */
    public function getAvailableLocalesProperty(): array
    {
        $locales = [];
        foreach (Locale::all() as $code => $meta) {
            $locales[$code] = [
                'code' => (string) $code,
                'label' => Locale::label((string) $code),
                'short' => strtoupper((string) $code),
                'flag' => Locale::flag((string) $code),
            ];
        }

        return $locales;
    }

    public function render(): View
    {
        return view('setting.livewire.lang-switch', [
            'availableLocales' => $this->availableLocales,
            'currentFlag' => Locale::flag($this->locale),
        ]);
    }
}
