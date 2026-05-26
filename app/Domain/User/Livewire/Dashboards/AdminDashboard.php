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

    public int $totalStudents = 0;

    public int $totalTeachers = 0;

    public int $totalDepartments = 0;

    public int $activeInternships = 0;

    public array $readiness = [];

    public function mount(GetAdminDashboardStatsAction $statsAction): void
    {
        $stats = $statsAction->execute();

        $this->totalStudents = $stats['totalStudents'];
        $this->totalTeachers = $stats['totalTeachers'];
        $this->totalDepartments = $stats['totalDepartments'];
        $this->activeInternships = $stats['activeInternships'];

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
            'stats' => [
                'students' => $this->totalStudents,
                'teachers' => $this->totalTeachers,
                'departments' => $this->totalDepartments,
                'internships' => $this->activeInternships,
            ],
            'readiness' => $this->readiness,
        ]);
    }
}
