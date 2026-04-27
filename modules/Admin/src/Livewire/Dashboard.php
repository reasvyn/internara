<?php

declare(strict_types=1);

namespace Modules\Admin\Livewire;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Modules\Admin\Analytics\Services\Contracts\AnalyticsAggregator;
use Modules\Assessment\Services\Contracts\AssessmentService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Permission\Enums\Role;

class Dashboard extends Component
{
    /**
     * The dashboard global filters.
     *
     * @var array{academic_year: string}
     */
    #[Url]
    public array $filters = [
        'academic_year' => '',
    ];

    /**
     * Initialize the component with the active academic year.
     */
    public function mount(): void
    {
        if (empty($this->filters['academic_year'])) {
            $this->filters['academic_year'] = (string) setting('active_academic_year');
        }
    }

    /**
     * Get the recent student registrations with pre-calculated performance data.
     */
    #[Computed]
    public function registrations(): array
    {
        $registrations = app(RegistrationService::class)
            ->query(['academic_year' => $this->filters['academic_year']])
            ->with([
                'student:id,name,username,avatar_url',
                'placement:id,company_id',
                'placement.company:id,name',
            ])
            ->latest()
            ->limit(10)
            ->get();

        if ($registrations->isEmpty()) {
            return [];
        }

        $registrationIds = $registrations->pluck('id')->toArray();
        $averageScores = app(AssessmentService::class)->getAverageScore($registrationIds);

        return $registrations
            ->map(function ($reg) use ($averageScores) {
                return [
                    'id' => (string) $reg->id,
                    'student' => [
                        'name' => $reg->student?->name ?? 'Unknown',
                        'username' => $reg->student?->username ?? '-',
                        'avatar_url' => $reg->student?->avatar_url,
                    ],
                    'company_name' => $reg->placement?->company?->name ?? '-',
                    'final_grade' => $averageScores[(string) $reg->id] ?? null,
                ];
            })
            ->toArray();
    }

    /**
     * Get available academic years from the system.
     */
    #[Computed]
    public function academicYears(): array
    {
        return DB::table('internships')
            ->select('academic_year')
            ->distinct()
            ->orderBy('academic_year', 'desc')
            ->pluck('academic_year', 'academic_year')
            ->toArray();
    }

    /**
     * Render the admin dashboard view.
     */
    public function render(AnalyticsAggregator $analytics): View
    {
        $user = auth()->user();
        $isSuperAdmin = $user->hasRole(Role::SUPER_ADMIN->value);

        $data = [
            'summary' => $analytics->getInstitutionalSummary($this->filters),
            'atRiskStudents' => $analytics->getAtRiskStudents(5, $this->filters),
            'recentActivities' => $analytics->getRecentActivities(8),
            'isSuperAdmin' => $isSuperAdmin,
        ];

        if ($isSuperAdmin) {
            $data['securitySummary'] = $analytics->getSecuritySummary();
            $data['infrastructure'] = $analytics->getInfrastructureStatus();
            $data['userDistribution'] = $analytics->getUserDistribution();
        }

        return view('admin::livewire.dashboard', $data)->layout(
            'ui::components.layouts.dashboard',
            [
                'title' =>
                    __('admin::ui.dashboard.title') .
                    ' | ' .
                    setting('brand_name', setting('app_name')),
                'context' => 'admin::ui.menu.dashboard',
            ],
        );
    }
}
