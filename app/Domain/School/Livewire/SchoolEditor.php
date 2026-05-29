<?php

declare(strict_types=1);

namespace App\Domain\School\Livewire;

use App\Domain\School\Actions\UpdateSchoolAction;
use App\Domain\School\Livewire\Forms\SchoolForm;
use App\Domain\School\Models\School;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class SchoolEditor extends Component
{
    use WithFileUploads;

    public SchoolForm $form;

    public $logo_file;

    public School $school;

    public bool $showConfirm = false;

    public function boot(): void
    {
        $this->authorize('update', School::class);
    }

    public function mount(): void
    {
        $this->school = School::firstOrFail();
        $this->form->fillFromModel($this->school);
    }

    public function rules(): array
    {
        $rules = [];
        foreach ($this->form->rules() as $key => $rule) {
            $rules['form.'.$key] = $rule;
        }
        $rules['form.institutional_code'][] = 'unique:schools,institutional_code,'.$this->school->id;
        $rules['form.email'][] = 'unique:schools,email,'.$this->school->id;
        $rules['logo_file'] = ['nullable', 'image', 'max:2048'];

        return $rules;
    }

    public function updatedLogoFile(UpdateSchoolAction $updateSchool): void
    {
        $this->validate(['logo_file' => ['nullable', 'image', 'max:2048']]);

        $updateSchool->execute($this->school, [
            'logo_file' => $this->logo_file,
        ]);

        flash()->success(__('school.logo_saved'));
        $this->dispatch('logo-updated');
    }

    public function removeLogo(UpdateSchoolAction $updateSchool): void
    {
        $this->school->clearMediaCollection(School::COLLECTION_LOGO);
        $this->logo_file = null;

        flash()->success(__('school.logo_removed'));
        $this->dispatch('logo-updated');
    }

    public function confirmAction(): void
    {
        $this->removeLogo(app(UpdateSchoolAction::class));
        $this->showConfirm = false;
    }

    public function logoPreviewUrl(): ?string
    {
        if ($this->logo_file === null) {
            return null;
        }

        try {
            return $this->logo_file->temporaryUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    public function save(UpdateSchoolAction $updateSchool): void
    {
        $this->validate();

        $data = $this->form->toArray();

        $updateSchool->execute($this->school, $data);

        flash()->success(__('school.save_success'));
        $this->dispatch('saved');
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('school.school-editor');
    }
}
