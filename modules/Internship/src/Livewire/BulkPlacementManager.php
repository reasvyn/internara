<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Modules\Internship\Models\Company;
use Modules\Internship\Models\Internship;
use Modules\Internship\Models\InternshipPlacement;
use Modules\Internship\Models\InternshipRegistration;

/**
 * Class BulkPlacementManager
 *
 * Manages bulk placement/plotting of students to companies (internship placements).
 * Allows admin/superadmin to assign multiple students to companies in batches.
 */
class BulkPlacementManager extends Component
{
    protected string $viewPermission = 'internship.manage';

    /**
     * Form state
     */
    public string $internshipId = '';

    public string $companyId = '';

    public string $mentorId = '';

    public array $selectedStudents = [];

    public array $placementResult = [];

    public bool $confirmModal = false;

    public string $resultMessage = '';

    public int $successCount = 0;

    public int $failureCount = 0;

    /**
     * Component lifecycle
     */
    public function mount(): void
    {
        $this->authorize('viewAny', InternshipPlacement::class);
    }

    /**
     * Get available internship programs
     */
    #[Computed]
    public function internships()
    {
        return Internship::query()->orderBy('title')->get()->map(
            fn(Internship $internship) => [
                'value' => $internship->id,
                'label' => "{$internship->title} ({$internship->academic_year})",
            ],
        );
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
            ->whereNotIn('id', function ($query) {
                $query
                    ->selectRaw('distinct company_id')
                    ->from('internship_placements')
                    ->where('internship_id', $this->internshipId);
            })
            ->orderBy('name')
            ->get()
            ->map(
                fn(Company $company) => [
                    'value' => $company->id,
                    'label' => "{$company->name} ({$company->business_field})",
                ],
            );
    }

    /**
     * Get students eligible for placement (not yet assigned to this placement)
     */
    #[Computed]
    public function availableStudents()
    {
        if (!$this->internshipId) {
            return [];
        }

        // Get students registered for this internship but not yet placed
        return InternshipRegistration::query()
            ->where('internship_id', $this->internshipId)
            ->whereNull('placement_id')
            ->with('student')
            ->get()
            ->map(function (InternshipRegistration $registration) {
                $student = $registration->student;

                return [
                    'id' => $registration->id,
                    'value' => $registration->id,
                    'label' => $student?->name ?? 'Unknown Student',
                    'student_id' => $registration->student_id,
                    'student_name' => $student?->name ?? 'Unknown',
                ];
            })
            ->sortBy('label')
            ->values();
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
            ->where('company_id', $this->companyId)
            ->where('internship_id', $this->internshipId)
            ->first();

        if (!$placement) {
            return 0;
        }

        $occupied = $placement->registrations()->count();

        return max(0, $placement->capacity_quota - $occupied);
    }

    /**
     * Show confirmation modal with placement summary
     */
    public function showConfirmation(): void
    {
        if (empty($this->selectedStudents)) {
            $this->dispatch(
                'notify',
                type: 'warning',
                message: __('internship::ui.select_students'),
            );

            return;
        }

        if (!$this->internshipId || !$this->companyId) {
            $this->dispatch(
                'notify',
                type: 'warning',
                message: __('internship::ui.select_internship_company'),
            );

            return;
        }

        if (count($this->selectedStudents) > $this->remainingQuota) {
            $this->dispatch(
                'notify',
                type: 'error',
                message: __('internship::ui.insufficient_quota', [
                    'selected' => count($this->selectedStudents),
                    'remaining' => $this->remainingQuota,
                ]),
            );

            return;
        }

        $this->confirmModal = true;
    }

    /**
     * Execute bulk placement
     */
    public function executePlacement(): void
    {
        try {
            $this->successCount = 0;
            $this->failureCount = 0;

            $placement = InternshipPlacement::query()
                ->where('company_id', $this->companyId)
                ->where('internship_id', $this->internshipId)
                ->firstOrFail();

            foreach ($this->selectedStudents as $registrationId) {
                try {
                    $registration = InternshipRegistration::query()->findOrFail($registrationId);

                    // Update registration with placement
                    $registration->update([
                        'placement_id' => $placement->id,
                    ]);

                    $this->successCount++;
                } catch (\Exception $e) {
                    $this->failureCount++;
                }
            }

            $company = $placement->company;
            $this->resultMessage = __('internship::ui.placement_success', [
                'count' => $this->successCount,
                'company' => $company->name,
            ]);

            // Reset form
            $this->resetForm();
            $this->confirmModal = false;
            $this->dispatch('notify', type: 'success', message: $this->resultMessage);
        } catch (\Exception $e) {
            $this->dispatch('notify', type: 'error', message: $e->getMessage());
        }
    }

    /**
     * Reset form
     */
    public function resetForm(): void
    {
        $this->internshipId = '';
        $this->companyId = '';
        $this->mentorId = '';
        $this->selectedStudents = [];
        $this->placementResult = [];
        $this->successCount = 0;
        $this->failureCount = 0;
    }

    /**
     * Render the component
     */
    public function render(): View
    {
        return view('internship::livewire.bulk-placement-manager')->layout(
            'ui::components.layouts.dashboard',
            [
                'title' =>
                    __('internship::ui.bulk_placement_title') .
                    ' | ' .
                    setting('brand_name', setting('app_name')),
                'context' => 'internship::ui.bulk_placement_context',
            ],
        );
    }
}
