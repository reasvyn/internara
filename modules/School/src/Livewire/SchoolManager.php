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
        $school = $this->schoolService->first()?->append('logo_url');

        if ($school) {
            $this->form->fill($school);
        }
    }

    public function save(): void
    {
        $this->authorize('school.manage');

        $this->form->validate();

        // Use ID for identification (WHERE), rest for data (values)
        $school = $this->schoolService->save(
            ['id' => $this->form->id],
            $this->form->except(['id', 'logo_file', 'logo_url']),
        );

        // Handle logo separately via Service (it checks for logo_file in the merged data or we can pass it explicitly)
        // Wait, Service->save calls parent->save then handleSchoolLogo.
        // handleSchoolLogo looks for 'logo_file' in the $data.
        // So we need to ensure logo_file is passed.

        // Let's pass logo_file in the values array so handleSchoolLogo can find it.
        $values = $this->form->except(['id', 'logo_url']);
        $school = $this->schoolService->save(['id' => $this->form->id], $values);

        // Refresh form with persisted data, including new logo URLs
        $this->form->fill($school);

        $this->dispatch('school_saved', schoolId: $school->id);
        $this->dispatch('notify', message: __('shared::messages.record_saved'), type: 'success');
    }

    public function render()
    {
        return view('school::livewire.school-manager');
    }
}
