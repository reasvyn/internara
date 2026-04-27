<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Internship\Livewire\Forms\RegistrationForm;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Internship\Services\Contracts\PlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\User;

class StudentPlacementManager extends RecordManager
{
    /**
     * Tab selection (individual|bulk)
     */
    public string $activeTab = 'individual';

    /**
     * Registration form for individual placement.
     */
    public RegistrationForm $form;

    /**
     * Bulk placement state variables.
     */
    public string $internshipId = '';

    public string $companyId = '';

    public array $selectedStudents = [];

    public bool $bulkConfirmModal = false;

    /**
     * Services required for placement operations.
     */
    protected PlacementService $placementService;

    /**
     * Initialize the component with necessary services.
     */
    public function boot(
        RegistrationService $registrationService,
        PlacementService $placementService,
    ): void {
        $this->service = $registrationService;
        $this->placementService = $placementService;
        $this->eventPrefix = 'registration';
        $this->modelClass = InternshipRegistration::class;
    }

    /**
     * Configure the component's basic properties and permissions.
     */
    public function initialize(): void
    {
        $this->title = __('internship::ui.student_placement_title');
        $this->subtitle = __('internship::ui.student_placement_subtitle');
        $this->context = 'internship::ui.index.title';
        $this->addLabel = __('internship::ui.place_student');
        $this->deleteConfirmMessage = __('internship::ui.delete_registration_confirm');

        $this->viewPermission = 'internship.view';
        $this->createPermission = 'internship.manage';
        $this->updatePermission = 'internship.manage';
        $this->deletePermission = 'internship.manage';

        $this->searchable = ['student.name', 'internship.title', 'placement.company.name'];
        $this->sortable = ['created_at', 'status'];
    }

    /**
     * Define the table structure for the individual placement tab.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'student_name', 'label' => __('internship::ui.student'), 'sortable' => false],
            [
                'key' => 'internship_title',
                'label' => __('internship::ui.program'),
                'sortable' => false,
            ],
            [
                'key' => 'placement_company',
                'label' => __('internship::ui.placement'),
                'sortable' => false,
            ],
            ['key' => 'teacher_name', 'label' => __('internship::ui.teacher'), 'sortable' => false],
            ['key' => 'status', 'label' => __('internship::ui.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1'],
        ];
    }

    /**
     * Fetch and transform records for the table.
     */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return $this->service
            ->query($this->filters)
            ->with(['student', 'internship', 'placement.company', 'teacher'])
            ->paginate($this->perPage)
            ->through(fn($registration) => $this->mapRecord($registration));
    }

    /**
     * Map a single registration record for UI presentation.
     */
    protected function mapRecord(mixed $record): array
    {
        return [
            'id' => $record->id,
            'student_name' => $record->student?->name ?? '-',
            'student_avatar' => $record->student?->avatar_url,
            'internship_title' => $record->internship?->title ?? '-',
            'placement_company' => $record->placement?->company?->name ?? '-',
            'proposed_company_name' => $record->proposed_company_name,
            'teacher_name' => $record->teacher?->name ?? '-',
            'status' => $record->status,
            'readiness' => $record->getRequirementCompletionPercentage(),
        ];
    }

    /**
     * Switch between placement modes (tabs).
     */
    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    // ──────────────────────────────────────────────────────────────────────
    // Dropdown Data Providers (Cached/Optimized)
    // ──────────────────────────────────────────────────────────────────────

    #[Computed]
    public function internships(): Collection
    {
        return app(InternshipService::class)
            ->all(['id', 'title', 'academic_year'])
            ->map(fn($i) => ['id' => $i->id, 'name' => "{$i->title} ({$i->academic_year})"]);
    }

    #[Computed]
    public function placements(): Collection
    {
        if (!$this->form->internship_id && !$this->internshipId) {
            return collect();
        }

        $id = $this->activeTab === 'individual' ? $this->form->internship_id : $this->internshipId;

        return InternshipPlacement::query()
            ->where('internship_id', $id)
            ->with('company')
            ->get()
            ->map(
                fn($p) => [
                    'id' => $p->id,
                    'name' => $p->company?->name ?? 'Unknown',
                    'quota' => $p->capacity_quota,
                ],
            );
    }

    #[Computed]
    public function students(): Collection
    {
        $id = $this->activeTab === 'individual' ? $this->form->internship_id : $this->internshipId;
        if (!$id) {
            return collect();
        }

        return InternshipRegistration::query()
            ->where('internship_id', $id)
            ->whereNull('placement_id')
            ->with('student')
            ->get()
            ->map(
                fn($r) => [
                    'id' => $r->id,
                    'name' => $r->student?->name ?? 'Unknown',
                    'email' => $r->student?->email ?? '-',
                ],
            );
    }

    #[Computed]
    public function teachers(): Collection
    {
        return User::role('teacher')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    #[Computed]
    public function mentors(): Collection
    {
        return User::role('mentor')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    // ──────────────────────────────────────────────────────────────────────
    // Bulk placement operations
    // ──────────────────────────────────────────────────────────────────────

    #[Computed]
    public function remainingQuota(): int
    {
        if (!$this->companyId || !$this->internshipId) {
            return 0;
        }

        $placement = InternshipPlacement::find($this->companyId);
        if (!$placement) {
            return 0;
        }

        $assigned = InternshipRegistration::where('placement_id', $placement->id)->count();

        return max(0, $placement->capacity_quota - $assigned);
    }

    public function showBulkConfirmation(): void
    {
        if (empty($this->selectedStudents)) {
            flash()->warning(__('internship::ui.select_at_least_one_student'));

            return;
        }

        if (count($this->selectedStudents) > $this->remainingQuota()) {
            flash()->error(
                __('internship::ui.insufficient_quota', [
                    'selected' => count($this->selectedStudents),
                    'remaining' => $this->remainingQuota(),
                ]),
            );

            return;
        }

        $this->bulkConfirmModal = true;
    }

    public function executeBulkPlacement(): void
    {
        try {
            $successCount = 0;
            foreach ($this->selectedStudents as $registrationId) {
                if ($this->placementService->assignPlacement($registrationId, $this->companyId)) {
                    $successCount++;
                }
            }

            $this->resetBulkForm();
            $this->bulkConfirmModal = false;

            flash()->success(
                __('internship::ui.bulk_placement_success', ['count' => $successCount]),
            );
            $this->refreshRecords();
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function resetBulkForm(): void
    {
        $this->internshipId = '';
        $this->companyId = '';
        $this->selectedStudents = [];
    }

    public function render(): View
    {
        return view('internship::livewire.student-placement-manager');
    }
}
