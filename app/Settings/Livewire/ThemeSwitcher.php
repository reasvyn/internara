<?php

declare(strict_types=1);

namespace App\Settings\Livewire;

use Illuminate\View\View;
use Livewire\Component;

class ThemeSwitcher extends Component
{
    public string $theme = 'system';

    public function mount(): void
    {
        $this->theme = request()->cookie('theme', 'system');
    }

    public function setTheme(string $theme): void
    {
        if (! in_array($theme, ['light', 'dark', 'system'], true)) {
            return;
        }

        $this->theme = $theme;

        cookie()->queue(cookie()->forever('theme', $theme));

        $this->dispatch('theme-changed', theme: $theme);
    }

    public function render(): View
    {
        return view('settings.livewire.theme-switcher');
    }
}
