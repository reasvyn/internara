<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Component;
use Modules\Core\Services\Contracts\AnalyticsAggregator;

class Dashboard extends Component
{
    /**
     * Render the admin dashboard view.
     */
    public function render(AnalyticsAggregator $analytics): View
    {
        return view('admin::livewire.dashboard', [
            'summary' => $analytics->getInstitutionalSummary(),
            'atRiskStudents' => $analytics->getAtRiskStudents(),
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('Dasbor Admin'),
        ]);
    }
}
