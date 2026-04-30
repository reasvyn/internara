<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Session;
use Livewire\Component;

/**
 * Language Switcher Component
 * 
 * S2 - Sustain: Simple language toggle with session storage
 */
class LanguageSwitcher extends Component
{
    public string $currentLocale;
    
    public function mount(): void
    {
        $this->currentLocale = Session::get('locale', config('app.locale'));
    }
    
    public function switchLanguage(string $locale): void
    {
        if (!in_array($locale, ['en', 'id'])) {
            return;
        }
        
        Session::put('locale', $locale);
        $this->currentLocale = $locale;
        
        $this->dispatch('language-changed', locale: $locale);
    }
    
    public function render()
    {
        return view('livewire.language-switcher', [
            'locales' => [
                'en' => 'EN',
                'id' => 'ID',
            ],
        ]);
    }
}
