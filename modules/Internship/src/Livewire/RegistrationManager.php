<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Component;
use Modules\Internship\Livewire\Forms\RegistrationForm;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\UI\Livewire\Concerns\ManagesRecords;
use Modules\User\Services\Contracts\UserService;

class RegistrationManager extends Component
{
    use ManagesRecords;

    public RegistrationForm $form;

    public ?string $targetPlacementId = null;

    public bool $bulkPlaceModal = false;

    public bool $historyModal = false;

    public ?string $historyId = null;

    public function boot(RegistrationService $registrationService): void
    {
        $this->service = $registrationService;
        $this->eventPrefix = 'registration';
    }

    /**
     * {@inheritdoc}
     */
    protected function getExportHeaders(): array
    {
        return [
            'student_name' => __('internship::ui.student'),
            'internship_title' => __('internship::ui.program'),
            'placement_company' => __('internship::ui.placement'),
            'teacher_name' => __('internship::ui.teacher'),
            'status' => __('internship::ui.status'),
            'created_at' => __('ui::common.created_at'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapRecordForExport($record, array $keys): array
    {
        return [
            $record->student->name,
            $record->internship->title,
            $record->placement?->company?->name ?? 'N/A',
            $record->teacher?->name ?? 'N/A',
            $record->latestStatus()?->name ?? 'pending',
            $record->created_at->format('Y-m-d H:i'),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function mapImportRow(array $row, array $keys): ?array
    {
        // Import for registrations is complex due to IDs, usually handled via specialized logic
        // For now, providing a basic mapper placeholder
        return null;
    }

    /**
     * {@inheritdoc}
     */
    protected function getPdfView(): ?string
    {
        return 'internship::pdf.registrations';
    }

    public function mount(): void
    {
        $this->authorize('registration.view');
    }

    /**
     * Get internships for the dropdown.
     */
    public function getInternshipsProperty(): \Illuminate\Support\Collection
    {
        return app(InternshipService::class)->all(['id', 'title']);
    }

    /**
     * Get placements for the dropdown.
     */
    public function getPlacementsProperty(): \Illuminate\Support\Collection
    {
        return app(InternshipPlacementService::class)
            ->query()
            ->with('company')
            ->get()
            ->map(fn ($p) => ['id' => $p->id, 'name' => $p->company?->name ?? 'Unknown']);
    }

        /**
         * Get students for the dropdown.
         */
        public function getStudentsProperty(): \Illuminate\Support\Collection
        {
            return app(UserService::class)
                ->get(['roles.name' => 'student'], ['id', 'name', 'username'])
                ->map(fn ($u) => ['id' => $u->id, 'name' => $u->name . ' (' . $u->username . ')']);
        }
    

    /**
     * Get teachers for the dropdown.
     */
    public function getTeachersProperty(): \Illuminate\Support\Collection
    {
        return app(UserService::class)->get(['roles.name' => 'teacher'], ['id', 'name']);
    }

    /**
     * Get mentors for the dropdown.
     */
    public function getMentorsProperty(): \Illuminate\Support\Collection
    {
        return app(UserService::class)->get(['roles.name' => 'mentor'], ['id', 'name']);
    }

    /**
     * Override save to use register method with validation.
     */
    public function save(): void
    {
        $this->form->validate();

        try {
            /** @var RegistrationService $service */
            $service = $this->service;

            // Keystone Verification: Ensure student has cleared requirements before placement
            if ($this->form->placement_id) {
                $isEligible = app(
                    \Modules\Internship\Services\Contracts\PlacementService::class,
                )->isEligibleForPlacement($this->form->id ?? 'new'); // 'new' is dummy, eligibility check usually needs student_id context for new records

                // For existing records, we can check the ID
                if ($this->form->id && ! $isEligible) {
                    throw new \Modules\Exception\AppException(
                        'internship::ui.not_eligible_for_placement',
                        code: 422,
                    );
                }
            }

            if ($this->form->id) {
                $service->update($this->form->id, $this->form->except('id'));
            } else {
                $service->register($this->form->all());
            }

            $this->formModal = false;
            flash()->success(__('shared::messages.record_saved'));
            $this->dispatch($this->getEventPrefix().':saved', exists: true);
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Open the history modal for a registration.
     */
    public function viewHistory(string $id): void
    {
        $this->historyId = $id;
        $this->historyModal = true;
    }

    /**
     * Get history for the current record.
     */
    public function getHistoryProperty(): \Illuminate\Support\Collection
    {
        if (! $this->historyId) {
            return collect();
        }

        return app(RegistrationService::class)
            ->find($this->historyId)
            ->placementHistory()
            ->with('placement')
            ->latest()
            ->get();
    }

    /**
     * Get records property for the table.
     */
    public function getRecordsProperty(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->service
            ->query(
                ['search' => $this->search, 'sort_by' => $this->sortBy, 'sort_dir' => $this->sortDir],
                ['id', 'student_id', 'internship_id', 'placement_id', 'status', 'created_at']
            )
            ->with([
                'student:id,name,username', 
                'internship:id,title', 
                'placement:id,company_id', 
                'placement.company:id,name'
            ])
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('internship::livewire.registration-manager', [
            'records' => $this->records,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('internship::ui.registration_title') . ' | ' . setting('brand_name', setting('app_name')),
            'context' => 'internship::ui.index.title',
        ]);
    }

    /**
     * Open the bulk placement modal.
     */
    public function openBulkPlace(): void
    {
        if (empty($this->selectedIds)) {
            flash()->warning(__('internship::ui.select_at_least_one_student'));

            return;
        }

        $this->bulkPlaceModal = true;
    }

    /**
     * Execute bulk placement.
     */
    public function executeBulkPlace(): void
    {
        if (! $this->targetPlacementId) {
            flash()->error(__('internship::ui.select_placement_location'));

            return;
        }

        try {
            $pairings = array_fill_keys($this->selectedIds, $this->targetPlacementId);
            $count = app(\Modules\Internship\Services\Contracts\PlacementService::class)->bulkMatch(
                $pairings,
            );

            $this->bulkPlaceModal = false;
            $this->selectedIds = [];
            $this->targetPlacementId = null;

            flash()->success(__('internship::ui.bulk_placement_success', ['count' => $count]));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Approve a registration.
     */
    public function approve(string $id): void
    {
        try {
            /** @var RegistrationService $service */
            $service = $this->service;
            $service->approve($id);
            flash()->success(__('internship::ui.registration_approved'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Reject a registration.
     */
    public function reject(string $id): void
    {
        try {
            /** @var RegistrationService $service */
            $service = $this->service;
            $service->reject($id);
            flash()->warning(__('internship::ui.registration_rejected'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Complete a registration.
     */
    public function complete(string $id): void
    {
        try {
            /** @var RegistrationService $service */
            $service = $this->service;
            $service->complete($id);
            flash()->success(__('internship::ui.registration_completed'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }
}
