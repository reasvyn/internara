<?php

declare(strict_types=1);

namespace App\Livewire\Layout;

use App\Support\AppInfo;
use App\Support\Integrity;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Displays author credits and application metadata.
 *
 * S2 - Sustain: Single source of truth for author attribution.
 */
class AppSignature extends Component
{
    /**
     * Render the component.
     */
    public function render(): View
    {
        Integrity::verify();

        return view('livewire.layout.app-signature', [
            'app_name' => AppInfo::get('name', config('app.name')),
            'app_version' => AppInfo::version(),
            'app_license' => AppInfo::get('license', 'MIT'),
            'author' => AppInfo::author(),
        ]);
    }
}
