<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Internship\Livewire\Forms\PlacementForm;
use Modules\Internship\Services\Contracts\InternshipPlacementService;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Livewire\Forms\UserForm;
use Modules\User\Services\Contracts\UserService;

class InternshipPlacementManager extends RecordManager
{
    protected string $viewPermission = 'placement.view';

    public PlacementForm $form;

    public UserForm $mentorForm;

    public bool $mentorModal = false;

    /**
     * Initialize the component.
     */
    public function boot(InternshipPlacementService $placementService): void
    {
        $this->service = $placementService;
    }

    public function initialize(): void {}

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'company', 'label' => __('internship::ui.company'), 'sortable' => false],
            ['key' => 'internship', 'label' => __('internship::ui.program'), 'sortable' => false],
            ['key' => 'mentor', 'label' => __('internship::ui.mentor'), 'sortable' => false],
            ['key' => 'created_at', 'label' => __('ui::common.created_at'), 'sortable' => true],
        ];
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        parent::mount();
    }

    /**
     * Get records for the table.
     */
    #[Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        return $this->service->paginate(
            [
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ],
            $this->perPage,
        );
    }

    /** Shared cache TTL (5 minutes) for dropdown lists that rarely change. */
    private const DROPDOWN_TTL = 300;

    /**
     * Get companies for the dropdown.
     */
    #[Computed]
    public function companies(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown:companies', self::DROPDOWN_TTL, fn () => \Modules\Internship\Models\Company::all(['id', 'name']));
    }

    /**
     * Get internships for the dropdown.
     */
    #[Computed]
    public function internships(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown:internships', self::DROPDOWN_TTL, fn () => app(InternshipService::class)->all(['id', 'title']));
    }

    /**
     * Get mentors for the dropdown.
     */
    #[Computed]
    public function mentors(): \Illuminate\Support\Collection
    {
        return Cache::remember('dropdown:users:mentor', self::DROPDOWN_TTL, fn () => app(UserService::class)->get(['roles.name' => 'mentor'], ['id', 'name']));
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
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('internship::ui.placement_title').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
