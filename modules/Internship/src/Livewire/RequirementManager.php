<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Modules\Internship\Livewire\Forms\RequirementForm;
use Modules\Internship\Services\Contracts\InternshipRequirementService;
use Modules\UI\Livewire\RecordManager;

class RequirementManager extends RecordManager
{
    public RequirementForm $form;

    /**
     * Initialize the component metadata and services.
     */
    public function boot(InternshipRequirementService $requirementService): void
    {
        $this->service = $requirementService;
        $this->eventPrefix = 'internship-requirement';
        $this->modelClass = \Modules\Internship\Models\InternshipRequirement::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('internship::ui.requirement_title');
        $this->subtitle = __('internship::ui.requirement_subtitle');
        $this->context = 'internship::ui.index.title';
        $this->addLabel = __('internship::ui.add_requirement');
        $this->deleteConfirmMessage = __('internship::ui.delete_requirement_confirm');

        $this->viewPermission = 'internship.view';
        $this->createPermission = 'internship.manage';
        $this->updatePermission = 'internship.manage';
        $this->deletePermission = 'internship.manage';
    }

    /**
     * Get summary metrics for internship requirements.
     */
    #[Computed]
    public function stats(): array
    {
        return [
            'total' => $this->service->query()->count(),
            'mandatory' => $this->service->query(['is_mandatory' => true])->count(),
            'active' => $this->service->query(['is_active' => true])->count(),
            'documents' => $this->service->query(['type' => 'document'])->count(),
        ];
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('internship::ui.requirement_name'), 'sortable' => true],
            ['key' => 'type', 'label' => __('internship::ui.requirement_type')],
            ['key' => 'is_mandatory', 'label' => __('internship::ui.mandatory')],
            ['key' => 'is_active', 'label' => __('internship::ui.active')],
            ['key' => 'academic_year', 'label' => __('internship::ui.academic_year'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'class' => 'w-1 text-right'],
        ];
    }

    /**
     * Transform raw requirement record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        return $record->toArray();
    }

    /**
     * Open the form modal for adding a new record.
     */
    public function add(): void
    {
        $this->form->reset();

        // Standard Auto-fills
        $this->form->academic_year = \Modules\Core\Academic\Support\AcademicYear::current();
        $this->form->is_active = true;

        $this->toggleModal(self::MODAL_FORM, true);
    }

    /**
     * Render the component view.
     */
    public function render(): \Illuminate\View\View
    {
        return view('internship::livewire.requirement-manager')
            ->layout('ui::components.layouts.dashboard', [
                'title' => $this->title . ' | ' . setting('brand_name', setting('app_name')),
            ]);
    }
}
