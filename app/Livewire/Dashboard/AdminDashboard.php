<?php

declare(strict_types=1);

namespace App\Livewire\Dashboard;

use App\Actions\Dashboard\GetAdminDashboardStatsAction;
use App\Services\SystemAuditService;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AdminDashboard extends Component
{
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
            'database' => ['label' => 'Database Connection', 'passed' => $results['database'] ?? true],
            'mail' => ['label' => 'Mail Configuration', 'passed' => $results['mail'] ?? true],
            'cache' => ['label' => 'Cache System', 'passed' => $results['cache'] ?? true],
            'queue' => ['label' => 'Queue Worker', 'passed' => $results['queue'] ?? true],
            'storage' => ['label' => 'Storage Link', 'passed' => $results['storage'] ?? true],
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
