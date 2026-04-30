<?php

declare(strict_types=1);

namespace App\Livewire;

use Illuminate\Support\Facades\Cookie;
use Livewire\Component;

/**
 * Theme Switcher Component
 * 
 * S2 - Sustain: Simple theme toggle with cookie storage
 */
class ThemeSwitcher extends Component
{
    public string $currentTheme;
    
    public function mount(): void
    {
        $this->currentTheme = Cookie::get('theme', 'system');
    }
    
    public function switchTheme(string $theme): void
    {
        if (!in_array($theme, ['light', 'dark', 'system'])) {
            return;
        }
        
        $this->currentTheme = $theme;
        Cookie::queue('theme', $theme, 60 * 24 * 365); // 1 year
        
        $this->dispatch('theme-changed', theme: $theme);
    }
    
    public function render()
    {
        return view('livewire.theme-switcher', [
            'themes' => [
                'light' => ['label' => __('light'), 'icon' => 'o-sun'],
                'dark' => ['label' => __('dark'), 'icon' => 'o-moon'],
                'system' => ['label' => __('system'), 'icon' => 'o-computer-desktop'],
            ],
        ]);
    }
}
