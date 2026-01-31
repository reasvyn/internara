<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire\Widgets;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Class AppInfoWidget
 *
 * A subtle widget to display application metadata and developer credits.
 */
class AppInfoWidget extends Component
{
    /**
     * Application information.
     */
    public array $appInfo;

    /**
     * Initialize the component.
     */
    public function mount(): void
    {
        $path = base_path('app_info.json');
        $this->appInfo = file_exists($path)
            ? json_decode(file_get_contents($path), true)
            : [];
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('admin::livewire.widgets.app-info-widget');
    }
}
