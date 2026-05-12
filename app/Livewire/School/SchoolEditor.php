<?php

declare(strict_types=1);

namespace App\Livewire\School;

use App\Actions\School\UpdateSchoolAction;
use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class SchoolEditor extends Component
{
    use WithFileUploads;

    public $logo_file;

    public School $school;

    public string $name = '';

    public string $institutional_code = '';

    public string $address = '';

    public string $principal_name = '';

    public string $email = '';

    public string $phone = '';

    public string $fax = '';

    public function boot(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('super_admin')) {
            abort(403);
        }
    }

    public function mount(): void
    {
        $this->school = School::firstOrFail();

        $this->name = $this->school->name;
        $this->institutional_code = $this->school->institutional_code;
        $this->address = $this->school->address ?? '';
        $this->principal_name = $this->school->principal_name ?? '';
        $this->email = $this->school->email ?? '';
        $this->phone = $this->school->phone ?? '';
        $this->fax = $this->school->fax ?? '';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institutional_code' => [
                'required',
                'string',
                'max:50',
                'unique:schools,institutional_code,'.$this->school->id,
            ],
            'address' => ['required', 'string', 'max:1000'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:schools,email,'.$this->school->id,
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'fax' => ['nullable', 'string', 'max:20'],
            'logo_file' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function save(UpdateSchoolAction $updateSchool): void
    {
        $validated = $this->validate();

        $validated['logo_file'] = $this->logo_file?->getRealPath() && file_exists($this->logo_file->getRealPath())
            ? $this->logo_file
            : null;

        $updateSchool->execute($this->school, $validated);

        flash()->success(__('school.save_success'));
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.school.school-editor');
    }
}
