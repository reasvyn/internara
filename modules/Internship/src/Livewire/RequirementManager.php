<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Component;
use Modules\Internship\Livewire\Forms\RequirementForm;
use Modules\Internship\Services\Contracts\InternshipRequirementService;
use Modules\Shared\Livewire\Concerns\ManagesRecords;

class RequirementManager extends Component
{
    use ManagesRecords;

    public RequirementForm $form;

    /**
     * Initialize the component.
     */
    public function boot(InternshipRequirementService $requirementService): void
    {
        $this->service = $requirementService;
        $this->eventPrefix = 'internship-requirement';
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->authorize('internship.view');
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
            'title' => __('internship::ui.requirement_title') . ' | ' . setting('brand_name', setting('app_name')),
        ]);
    }
}
