<?php

declare(strict_types=1);

namespace Modules\School\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\School\Livewire\Forms\SchoolForm;
use Modules\School\Services\Contracts\SchoolService;

/**
 * Class SchoolManager
 *
 * Orchestrates the management of institutional metadata, including branding and contact information.
 */
class SchoolManager extends Component
{
    use WithFileUploads;

    /**
     * The form object holding school data.
     */
    public SchoolForm $form;

    /**
     * The service responsible for institutional logic.
     */
    protected SchoolService $schoolService;

    /**
     * Injects dependencies into the component.
     */
    public function boot(SchoolService $schoolService): void
    {
        $this->schoolService = $schoolService;
    }

    /**
     * Initializes the component state.
     */
    public function mount(): void
    {
        $this->loadSchoolData();
    }

    /**
     * Retrieves the current school record and populates the form.
     */
    protected function loadSchoolData(): void
    {
        $school = $this->schoolService->getSchool();

        if ($school) {
            $this->form->fill($school->toArray());
        }
    }

    /**
     * Persists institutional changes to the database.
     */
    public function save(): void
    {
        // Permission Bypass: Authorized setup sessions can manage school data without explicit 'manage' permission.
        $isSetupAuthorized =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
            true;

        if (! $isSetupAuthorized) {
            $this->authorize('school.manage');
        }

        $this->form->validate();

        // Pass attributes to the service for persistence
        $school = $this->schoolService->save(
            ['id' => $this->form->id],
            $this->form->except(['id', 'logo_url']),
        );

        // Synchronize form with the fresh record state
        $this->form->fill($school->toArray());

        flash()->success(__('shared::messages.record_saved'));

        $this->dispatch('school_saved', schoolId: $school->id);
    }

    /**
     * Renders the administrative interface.
     */
    public function render(): \Illuminate\View\View
    {
        return view('school::livewire.school-manager')->layout('ui::components.layouts.dashboard', [
            'title' => __('school::ui.settings').' | '.setting('brand_name', setting('app_name')),
        ]);
    }
}
