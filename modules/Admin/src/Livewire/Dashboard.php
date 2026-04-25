<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Admin\Analytics\Services\Contracts\AnalyticsAggregator;
use Modules\Internship\Services\Contracts\RegistrationService;

class Dashboard extends Component
{
    /**
     * Get the current student registration.
     */
    #[Computed]
    public function registrations(): object
    {
        return app(RegistrationService::class)->paginate([], 10);
    }

    /**
     * Render the admin dashboard view.
     */
    public function render(AnalyticsAggregator $analytics): View
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(\Modules\Permission\Enums\Role::SUPER_ADMIN->value);

        $data = [
            'summary' => $analytics->getInstitutionalSummary(),
            'atRiskStudents' => $analytics->getAtRiskStudents(),
            'isSuperAdmin' => $isSuperAdmin,
        ];

        if ($isSuperAdmin) {
            $data['securitySummary'] = $analytics->getSecuritySummary();
            $data['infrastructure'] = $analytics->getInfrastructureStatus();
            $data['userDistribution'] = $analytics->getUserDistribution();
        }

        return view('admin::livewire.dashboard', $data)->layout('ui::components.layouts.dashboard', [
            'title' => __('admin::ui.dashboard.title').
                ' | '.
                setting('brand_name', setting('app_name')),
            'context' => 'admin::ui.menu.dashboard',
        ]);
    }
}
