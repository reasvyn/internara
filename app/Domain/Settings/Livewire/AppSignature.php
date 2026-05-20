<?php

declare(strict_types=1);

namespace App\Domain\Settings\Livewire;

use App\Domain\Settings\Support\AppInfo;
use Illuminate\View\View;
use Livewire\Component;

class AppSignature extends Component
{
    public function render(): View
    {
        return view('settings.app-signature', [
            'app_name' => AppInfo::get('name', config('app.name')),
            'app_version' => AppInfo::version(),
            'app_license' => AppInfo::get('license', 'MIT'),
            'author' => AppInfo::author(),
        ]);
    }
}
