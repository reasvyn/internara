<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Actions\Dashboard\GetAdminDashboardStatsAction;
use App\Services\SystemAuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AdminDashboard extends Component
{
    public function boot(): void
    {
        abort_unless(auth()->user()->hasAnyRole(['super_admin', 'admin']), 403);
    }

    public int $totalStudents = 0;

    public int $totalTeachers = 0;

    public int $totalDepartments = 0;

    public int $activeInternships = 0;

    public array $readiness = [];

    public function mount(GetAdminDashboardStatsAction $statsAction, SystemAuditService $auditService): void
    {
        $stats = $statsAction->execute();

        $this->totalStudents = $stats['totalStudents'];
        $this->totalTeachers = $stats['totalTeachers'];
        $this->totalDepartments = $stats['totalDepartments'];
        $this->activeInternships = $stats['activeInternships'];

        $results = $auditService->run();
        $this->readiness = [
            'database' => ['label' => __('dashboard.readiness.database'), 'passed' => $results['database'] ?? true],
            'mail' => ['label' => __('dashboard.readiness.mail'), 'passed' => $results['mail'] ?? true],
            'cache' => ['label' => __('dashboard.readiness.cache'), 'passed' => $results['cache'] ?? true],
            'queue' => ['label' => __('dashboard.readiness.queue'), 'passed' => $results['queue'] ?? true],
            'storage' => ['label' => __('dashboard.readiness.storage'), 'passed' => $results['storage'] ?? true],
        ];
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.dashboard.admin', [
            'stats' => [
                'students' => $this->totalStudents,
                'teachers' => $this->totalTeachers,
                'departments' => $this->totalDepartments,
                'internships' => $this->activeInternships,
            ],
        ]);
    }
}
