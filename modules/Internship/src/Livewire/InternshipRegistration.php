<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Modules\Exception\AppException;
use Modules\Internship\Livewire\Forms\RegistrationForm;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\Internship\Services\Contracts\PlacementService;
use Modules\Internship\Services\Contracts\RegistrationService;
use Modules\Permission\Enums\Permission;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Services\Contracts\UserService;

class InternshipRegistration extends RecordManager
{
    protected ?Permission $viewPermission = Permission::REGISTRATION_VIEW;

    protected array $sortable = ['created_at', 'status'];

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

    public function initialize(): void
    {
        // Service and eventPrefix are set in boot(); nothing more needed here.
    }

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
            [
                'key' => 'current_status',
                'label' => __('internship::ui.status'),
                'sortable' => true,
                'sort_by' => 'status',
            ],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1 text-right'],
        ];
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
        parent::mount();
    }

    /** Shared cache TTL (5 minutes) for dropdown lists that rarely change. */
    private const DROPDOWN_TTL = 300;

    /**
     * Get internships for the dropdown.
     * Result is cached across all users to reduce repeated DB reads.
     */
    #[Computed]
    public function internships(): Collection
    {
        return Cache::remember(
            'dropdown:internships',
            self::DROPDOWN_TTL,
            fn() => app(InternshipService::class)->all(['id', 'title']),
        );
    }

    /**
     * Get placements with company names for the dropdown.
     * Result is cached across all users to reduce repeated DB reads.
     */
    #[Computed]
    public function placements(): Collection
    {
        return Cache::remember('dropdown:placements', self::DROPDOWN_TTL, function () {
            return app(InternshipPlacementService::class)
                ->query()
                ->with('company:id,name')
                ->get(['id', 'company_id'])
                ->map(fn($p) => ['id' => $p->id, 'name' => $p->company?->name ?? 'Unknown']);
        });
    }

    /**
     * Get students for the dropdown.
     * Result is cached across all users to reduce repeated DB reads.
     */
    #[Computed]
    public function students(): Collection
    {
        return Cache::remember('dropdown:users:student', self::DROPDOWN_TTL, function () {
            return app(UserService::class)
                ->get(['roles.name' => 'student'], ['id', 'name', 'username'])
                ->map(fn($u) => ['id' => $u->id, 'name' => $u->name . ' (' . $u->username . ')']);
        });
    }

    /**
     * Get teachers for the dropdown.
     * Result is cached across all users to reduce repeated DB reads.
     */
    #[Computed]
    public function teachers(): Collection
    {
        return Cache::remember(
            'dropdown:users:teacher',
            self::DROPDOWN_TTL,
            fn() => app(UserService::class)->get(['roles.name' => 'teacher'], ['id', 'name']),
        );
    }

    /**
     * Get mentors for the dropdown.
     * Result is cached across all users to reduce repeated DB reads.
     */
    #[Computed]
    public function mentors(): Collection
    {
        return Cache::remember(
            'dropdown:users:mentor',
            self::DROPDOWN_TTL,
            fn() => app(UserService::class)->get(['roles.name' => 'mentor'], ['id', 'name']),
        );
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
                $isEligible = app(PlacementService::class)->isEligibleForPlacement(
                    $this->form->id ?? 'new',
                ); // 'new' is dummy, eligibility check usually needs student_id context for new records

                // For existing records, we can check the ID
                if ($this->form->id && !$isEligible) {
                    throw new AppException('internship::ui.not_eligible_for_placement', code: 422);
                }
            }

            if ($this->form->id) {
                $service->update($this->form->id, $this->form->except('id'));
            } else {
                $service->register($this->form->all());
            }

            $this->formModal = false;
            flash()->success(__('shared::messages.record_saved'));
            $this->dispatch($this->getEventPrefix() . ':saved', exists: true);
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
     * Get placement history for the selected registration.
     */
    #[Computed]
    public function history(): Collection
    {
        if (!$this->historyId) {
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
     * Define relationships to eager load.
     */
    protected function getWith(): array
    {
        return [
            'student:id,name,username',
            'internship:id,title',
            'placement:id,company_id',
            'placement.company:id,name',
        ];
    }

    /**
     * Define specific columns for the query.
     */
    protected function getColumns(): array
    {
        return ['id', 'student_id', 'internship_id', 'placement_id', 'status', 'created_at'];
    }

    public function render()
    {
        return view('internship::livewire.internship-registration', [
            'records' => $this->records,
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
        if (!$this->targetPlacementId) {
            flash()->error(__('internship::ui.select_placement_location'));

            return;
        }

        try {
            $pairings = array_fill_keys($this->selectedIds, $this->targetPlacementId);
            $count = app(PlacementService::class)->bulkMatch($pairings);

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
