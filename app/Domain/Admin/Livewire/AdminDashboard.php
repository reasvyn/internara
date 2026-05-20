<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Actions\GetAdminDashboardStatsAction;
use App\Domain\Admin\Actions\SendNotificationAction;
use App\Domain\Admin\Models\Notification;
use Illuminate\View\View;
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

    public function mount(
        GetAdminDashboardStatsAction $statsAction,
        SendNotificationAction $sendNotification,
    ): void {
        $stats = $statsAction->execute();

        $this->totalStudents = $stats['totalStudents'];
        $this->totalTeachers = $stats['totalTeachers'];
        $this->totalDepartments = $stats['totalDepartments'];
        $this->activeInternships = $stats['activeInternships'];

        // Send welcome notification on first dashboard visit for Super Admin
        $user = auth()->user();
        if ($user->hasRole('super_admin')) {
            $hasWelcome = Notification::where('user_id', $user->id)
                ->where('type', 'welcome')
                ->exists();

            if (! $hasWelcome) {
                $sendNotification->execute(
                    userId: $user->id,
                    type: 'welcome',
                    title: __('notifications.welcome_to_dashboard.title'),
                    message: __('notifications.welcome_to_dashboard.message'),
                    link: route('admin.dashboard'),
                );
            }
        }

        $this->readiness = [
            'database' => ['label' => __('dashboard.readiness.database'), 'passed' => true],
            'mail' => ['label' => __('dashboard.readiness.mail'), 'passed' => true],
            'cache' => ['label' => __('dashboard.readiness.cache'), 'passed' => true],
            'queue' => ['label' => __('dashboard.readiness.queue'), 'passed' => true],
            'storage' => ['label' => __('dashboard.readiness.storage'), 'passed' => true],
        ];
    }

    #[Layout('layouts::app')]
    public function render(): View
    {
        return view('admin.dashboard', [
            'stats' => [
                'students' => $this->totalStudents,
                'teachers' => $this->totalTeachers,
                'departments' => $this->totalDepartments,
                'internships' => $this->activeInternships,
            ],
        ]);
    }
}
