<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Modules\Internship\Livewire\Forms\RequirementForm;
use Modules\Internship\Services\Contracts\InternshipRequirementService;
use Modules\UI\Livewire\RecordManager;

class RequirementManager extends RecordManager
{
    protected string $viewPermission = 'internship.view';

    public RequirementForm $form;

    /**
     * Initialize the component.
     */
    public function boot(InternshipRequirementService $requirementService): void
    {
        $this->service = $requirementService;
        $this->eventPrefix = 'internship-requirement';
    }

    public function initialize(): void {}

    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'academic_year', 'label' => __('internship::ui.academic_year'), 'sortable' => true],
            ['key' => 'title', 'label' => __('ui::common.name'), 'sortable' => true],
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

    /**
     * Open the form modal for adding a new record.
     */
    public function add(): void
    {
        $this->form->reset();

        // Default academic year - in a real app this would come from settings
        $this->form->academic_year = date('Y').'/'.(date('Y') + 1);

        $this->formModal = true;
    }

    public function render()
    {
        return view('internship::livewire.requirement-manager', [
            'records' => $this->records,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => __('internship::ui.requirement_title').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
