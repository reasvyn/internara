<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Internship\Livewire\Forms\PlacementForm;
use Modules\Internship\Services\Contracts\PlacementService;
use Modules\Permission\Enums\Permission;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Services\Contracts\UserService;

class InternshipPlacementManager extends RecordManager
{
    public PlacementForm $form;

    /**
     * Get summary statistics for internship placements.
     */
    #[Computed]
    public function stats(): array
    {
        return $this->service->getStats();
    }

    public UserForm $mentorForm;

    public bool $mentorModal = false;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(InternshipPlacementService $placementService): void
    {
        $this->service = $placementService;
        $this->eventPrefix = 'placement';
        $this->modelClass = InternshipPlacement::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('internship::ui.placement_title');
        $this->subtitle = __('internship::ui.placement_subtitle');
        $this->context = 'internship::ui.index.title';
        $this->addLabel = __('internship::ui.add_placement');
        $this->deleteConfirmMessage = __('internship::ui.delete_placement_confirm');

        $this->viewPermission = Permission::INTERNSHIP_MANAGE;
        $this->createPermission = Permission::INTERNSHIP_MANAGE;
        $this->updatePermission = Permission::INTERNSHIP_MANAGE;
        $this->deletePermission = Permission::INTERNSHIP_MANAGE;
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'company_name', 'label' => __('internship::ui.company'), 'sortable' => true],
            [
                'key' => 'internship_title',
                'label' => __('internship::ui.program'),
                'sortable' => true,
            ],
            ['key' => 'quota', 'label' => __('internship::ui.capacity_quota')],
            ['key' => 'mentor_name', 'label' => __('internship::ui.mentor')],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1 text-right'],
        ];
    }

    /**
     * Transform raw placement record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return array_merge($record->toArray(), [
            'company_name' => $record->company?->name ?? '-',
            'internship_title' => $record->internship?->title ?? '-',
            'mentor_name' => $record->mentor?->name ?? '-',
            'remaining_slots' => $record->remainingSlots,
            'utilization_percentage' => $record->utilizationPercentage,
        ]);
    }

    /** Shared cache TTL (5 minutes) for dropdown lists that rarely change. */
    private const DROPDOWN_TTL = 300;

    /**
     * Get companies for the dropdown.
     */
    #[Computed]
    public function companies(): Collection
    {
        return Cache::remember(
            'dropdown:companies',
            self::DROPDOWN_TTL,
            fn() => Company::all(['id', 'name']),
        );
    }

    /**
     * Get internships for the dropdown.
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
     * Get mentors for the dropdown.
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
     * Open the mentor creation modal.
     */
    public function addMentor(): void
    {
        $this->mentorForm->reset();
        $this->mentorForm->roles = ['mentor'];
        $this->mentorModal = true;
    }

    /**
     * Save the new mentor and auto-select them.
     */
    public function saveMentor(): void
    {
        $this->mentorForm->validate();

        try {
            $mentor = app(UserService::class)->create($this->mentorForm->all());

            $this->form->mentor_id = $mentor->id;
            $this->mentorModal = false;

            flash()->success(__('Mentor created and assigned successfully.'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    /**
     * Save the placement record.
     */
    public function save(): void
    {
        $this->form->validate();

        try {
            $this->service->save(['id' => $this->form->id], $this->form->all());

            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success(__('shared::messages.record_saved'));
        } catch (\Throwable $e) {
            if (is_debug_mode()) {
                throw $e;
            }
            flash()->error(__('shared::messages.error_occurred'));
        }
    }

    public function render(): View
    {
        return view('internship::livewire.internship-placement-manager', [
            'records' => $this->records,
        ]);
    }
}
