<?php

declare(strict_types=1);

namespace Modules\School\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\School\Livewire\Forms\SchoolForm;
use Modules\School\Services\Contracts\SchoolService;

class SchoolManager extends Component
{
    use WithFileUploads;

    public SchoolForm $form;

    protected SchoolService $schoolService;

    public function boot(SchoolService $schoolService): void
    {
        $this->schoolService = $schoolService;
    }

    public function mount(): void
    {
        $this->initFormData();
    }

    protected function initFormData(): void
    {
        $school = $this->schoolService->getSchool();

        if ($school) {
            // Explicitly ensure logo_url is present in the fill data
            $data = array_merge($school->toArray(), [
                'logo_url' => $school->logo_url,
            ]);
            $this->form->fill($data);
        }
    }

    public function save(): void
    {
        // Allow saving without 'school.manage' permission ONLY during the initial setup wizard.
        // The wizard is already protected by Signed URLs and specific session tokens.
        $isSetupPhase =
            session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) ===
            true;

        if (! $isSetupPhase) {
            $this->authorize('school.manage');
        }

        $this->form->validate();

        // Pass all relevant values including logo_file to the service
        $values = $this->form->except(['id', 'logo_url']);
        $school = $this->schoolService->save(['id' => $this->form->id], $values);

        // Refresh form with persisted data, including new logo URLs
        $this->form->fill($school->append('logo_url'));

        flash()->success(__('shared::messages.record_saved'));
        $this->dispatch('school_saved', schoolId: $school->id);
    }

    public function render()
    {
        return view('school::livewire.school-manager');
    }
}
