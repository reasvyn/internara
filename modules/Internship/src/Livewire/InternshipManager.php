<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Component;
use Modules\Internship\Livewire\Forms\InternshipForm;
use Modules\Internship\Services\Contracts\InternshipService;
use Modules\School\Services\Contracts\SchoolService;
use Modules\Shared\Livewire\Concerns\ManagesRecords;

class InternshipManager extends Component
{
    use ManagesRecords;

    public InternshipForm $form;

    /**
     * Initialize the component.
     */
    public function boot(InternshipService $internshipService): void
    {
        $this->service = $internshipService;
        $this->eventPrefix = 'internship';
    }

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $isSetupPhase =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
                true || is_testing();

        if ($isSetupPhase) {
            return;
        }

        $this->authorize('internship.view');
    }

    /**
     * Open the form modal for adding a new record.
     */
    public function add(): void
    {
        $this->form->reset();

        // Auto-fill school_id if only one school exists
        $school = app(SchoolService::class)->getSchool();
        if ($school) {
            $this->form->school_id = $school->id;
        }

        $this->formModal = true;
    }

    public function render()
    {
        return view('internship::livewire.internship-manager', [
            'records' => $this->records,
        ]);
    }
}
