<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Internship\Livewire\Forms\RegistrationForm;
use Modules\Internship\Models\Company;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Class InternshipRegistrationManager
 *
 * Unified manager for internship registrations with two placement modes:
 * 1. Individual Placement: One-by-one student assignment
 * 2. Bulk Placement: Batch assign multiple students to same location
 *
 * Provides efficient UX for managing large volumes of student placements.
 */
class InternshipRegistrationManager extends RecordManager
{
    use \Livewire\WithFileUploads;

    /**
     * Tab selection (individual|bulk)
     */
    public string $activeTab = 'individual';

    /**
     * Registration tab (RecordManager) state
     */
    protected string $viewPermission = 'registration.view';
    protected array $sortable = ['created_at', 'status'];
    public RegistrationForm $form;
    public ?string $targetPlacementId = null;
    public bool $historyModal = false;
    public ?string $historyId = null;

    /**
     * Bulk placement tab state
     */
    public string $internshipId = '';
    public string $companyId = '';
    public string $mentorId = '';
    public array $selectedStudents = [];
    public array $placementResult = [];
    public bool $bulkConfirmModal = false;
    public string $resultMessage = '';
    public int $successCount = 0;
    public int $failureCount = 0;

    public function boot(
        RegistrationService $registrationService,
        InternshipService $internshipService,
        UserService $userService,
        InternshipPlacementService $placementService,
    ): void {
        $this->service = $registrationService;
        $this->eventPrefix = 'registration';
    }

    public function initialize(): void
    {
        $this->title = __('internship::ui.student_placement_title');
        $this->subtitle = __('internship::ui.student_placement_subtitle');
        $this->context = 'internship::ui.index.title';
        $this->addLabel = __('internship::ui.place_student');
        $this->deleteConfirmMessage = __('internship::ui.delete_registration_confirm');
    }

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'student_name', 'label' => __('internship::ui.student'), 'sortable' => false],
            ['key' => 'internship_title', 'label' => __('internship::ui.program'), 'sortable' => false],
            ['key' => 'placement_company', 'label' => __('internship::ui.placement'), 'sortable' => false],
            ['key' => 'teacher_name', 'label' => __('internship::ui.teacher'), 'sortable' => false],
            ['key' => 'status', 'label' => __('internship::ui.status'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
        ];
    }

    // ──────────────────────────────────────────────────────────────────────
    // Individual Placement Tab (RecordManager)
    // ──────────────────────────────────────────────────────────────────────

    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return $this->service->query($this->filters)
            ->with(['student', 'internship', 'placement', 'teacher'])
            ->paginate($this->perPage)
            ->through(function ($registration) {
                return (object) [
                    'id' => $registration->id,
                    'student_name' => $registration->student?->name ?? '-',
                    'internship_title' => $registration->internship?->title ?? '-',
                    'placement_company' => $registration->placement?->company?->name ?? '-',
                    'teacher_name' => $registration->teacher?->name ?? '-',
                    'status' => $registration->status,
                ];
            });
    }

    public function edit(mixed $id): void
    {
        $record = $this->service->find($id);
        if ($record) {
            if (!$this->can('update', $record)) {
                $this->authorize('update', $record);
            }
            if (property_exists($this, 'form')) {
                if (method_exists($this->form, 'setRegistration')) {
                    $this->form->setRegistration($record);
                } else {
                    $this->form->fill($record);
                }
                $this->toggleModal(self::MODAL_FORM, true, ['id' => $id]);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────
    // Bulk Placement Tab
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Get available internship programs
     */
    #[Computed]
    public function internships()
    {
        return Internship::query()
            ->orderBy('title')
            ->get()
            ->map(fn (Internship $internship) => [
                'value' => $internship->id,
                'label' => "{$internship->title} ({$internship->academic_year})",
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get available companies for placement
     */
    #[Computed]
    public function companies()
    {
        if (!$this->internshipId) {
            return [];
        }

        return Company::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Company $company) => [
                'value' => $company->id,
                'label' => "{$company->name} ({$company->business_field})",
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get available unplaced students for bulk placement
     */
    #[Computed]
    public function availableStudents()
    {
        if (!$this->internshipId) {
            return [];
        }

        return InternshipRegistration::query()
            ->where('internship_id', $this->internshipId)
            ->whereNull('placement_id')
            ->with('student')
            ->get()
            ->map(fn (InternshipRegistration $reg) => [
                'id' => $reg->id,
                'student_id' => $reg->student_id,
                'name' => $reg->student?->name ?? 'Unknown',
                'email' => $reg->student?->email ?? '-',
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get remaining quota for selected company
     */
    #[Computed]
    public function remainingQuota()
    {
        if (!$this->companyId || !$this->internshipId) {
            return 0;
        }

        $placement = InternshipPlacement::query()
            ->where('internship_id', $this->internshipId)
            ->where('company_id', $this->companyId)
            ->first();

        if (!$placement) {
            return 0;
        }

        $assigned = InternshipRegistration::query()
            ->where('internship_id', $this->internshipId)
            ->where('placement_id', $placement->id)
            ->count();

        return max(0, $placement->quota - $assigned);
    }

    /**
     * Show bulk placement confirmation dialog
     */
    public function showBulkConfirmation(): void
    {
        if (empty($this->selectedStudents)) {
            flash()->warning(__('internship::ui.select_at_least_one_student'));
            return;
        }

        if (!$this->companyId || !$this->internshipId) {
            flash()->warning(__('internship::ui.select_internship_company'));
            return;
        }

        if (count($this->selectedStudents) > $this->remainingQuota()) {
            flash()->error(__('internship::ui.insufficient_quota', [
                'selected' => count($this->selectedStudents),
                'remaining' => $this->remainingQuota(),
            ]));
            return;
        }

        $this->bulkConfirmModal = true;
    }

    /**
     * Execute bulk placement
     */
    public function executeBulkPlacement(): void
    {
        try {
            $placement = InternshipPlacement::query()
                ->where('internship_id', $this->internshipId)
                ->where('company_id', $this->companyId)
                ->first();

            if (!$placement) {
                throw new \Exception(__('internship::ui.placement_location_not_found'));
            }

            $registrations = InternshipRegistration::query()
                ->whereIn('id', $this->selectedStudents)
                ->get();

            $this->successCount = 0;
            $this->failureCount = 0;
            $this->placementResult = [];

            foreach ($registrations as $registration) {
                try {
                    $registration->update([
                        'placement_id' => $placement->id,
                        'status' => 'approved',
                    ]);
                    $this->successCount++;
                    $this->placementResult[] = [
                        'name' => $registration->student?->name,
                        'status' => 'success',
                    ];
                } catch (\Exception $e) {
                    $this->failureCount++;
                    $this->placementResult[] = [
                        'name' => $registration->student?->name,
                        'status' => 'error',
                        'message' => $e->getMessage(),
                    ];
                }
            }

            $this->bulkConfirmModal = false;
            $this->selectedStudents = [];
            $this->internshipId = '';
            $this->companyId = '';

            flash()->success(__('internship::ui.bulk_placement_success', ['count' => $this->successCount]));
            $this->dispatch($this->getEventPrefix().':bulk-placed', count: $this->successCount);
        } catch (\Exception $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Reset bulk placement form
     */
    public function resetBulkForm(): void
    {
        $this->internshipId = '';
        $this->companyId = '';
        $this->selectedStudents = [];
        $this->placementResult = [];
        $this->successCount = 0;
        $this->failureCount = 0;
    }

    /**
     * Render the component view.
     */

    /**
     * Get students for form dropdown (not yet placed)
     */
    public function getStudents(): array
    {
        if (!$this->form->internship_id) {
            return [];
        }

        return InternshipRegistration::query()
            ->where('internship_id', $this->form->internship_id)
            ->whereNull('placement_id')
            ->with('student')
            ->get()
            ->map(fn ($reg) => [
                'value' => $reg->student_id,
                'label' => $reg->student?->name ?? 'Unknown',
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get placements (company locations) for form dropdown
     */
    public function getPlacements(): array
    {
        if (!$this->form->internship_id) {
            return [];
        }

        return InternshipPlacement::query()
            ->where('internship_id', $this->form->internship_id)
            ->with('company')
            ->get()
            ->map(fn ($placement) => [
                'value' => $placement->id,
                'label' => $placement->company?->name ?? 'Unknown',
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get teachers for form dropdown
     */
    public function getTeachers(): array
    {
        return User::role('teacher')
            ->orderBy('name')
            ->get()
            ->map(fn ($teacher) => [
                'value' => $teacher->id,
                'label' => $teacher->name,
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get mentors for form dropdown
     */
    public function getMentors(): array
    {
        return User::role('mentor')
            ->orderBy('name')
            ->get()
            ->map(fn ($mentor) => [
                'value' => $mentor->id,
                'label' => $mentor->name,
            ])
            ->values()
            ->toArray();
    }

    public function render(): View
    {
        return view('internship::livewire.internship-registration-manager')
            ->layout('ui::components.layouts.dashboard', [
                'title' => $this->title . ' | ' . setting('brand_name', setting('app_name')),
            ]);
    }
}
