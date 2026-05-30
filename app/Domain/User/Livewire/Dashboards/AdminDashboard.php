<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire\Dashboards;

use App\Domain\Admin\Actions\GetAdminDashboardStatsAction;
use App\Domain\User\Livewire\UserDashboard;
use Illuminate\View\View;

class AdminDashboard extends UserDashboard
{
    public function boot(): void
    {
        abort_unless(auth()->user()?->hasAnyRole(['super_admin', 'admin']), 403);
    }

    public array $stats = [];

    public array $readiness = [];

    public function mount(GetAdminDashboardStatsAction $statsAction): void
    {
        $this->stats = $statsAction->execute();

        $this->readiness = [
            'database' => ['label' => __('dashboard.readiness.database'), 'passed' => true],
            'mail' => ['label' => __('dashboard.readiness.mail'), 'passed' => true],
            'cache' => ['label' => __('dashboard.readiness.cache'), 'passed' => true],
            'queue' => ['label' => __('dashboard.readiness.queue'), 'passed' => true],
            'storage' => ['label' => __('dashboard.readiness.storage'), 'passed' => true],
        ];
    }

    public function render(): View
    {
        return view('user.dashboards.admin', [
            'roleContent' => true,
            'stats' => $this->stats,
            'readiness' => $this->readiness,
        ]);
    }
}
