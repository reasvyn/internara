<?php

declare(strict_types=1);

namespace App\Livewire\Admin\School;

use App\Actions\School\UpdateSchoolAction;
use App\Models\School;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SchoolProfile extends Component
{
    public School $school;

    public string $name = '';
    public string $institutional_code = '';
    public string $address = '';
    public string $principal_name = '';
    public string $email = '';
    public string $phone = '';

    public function mount(): void
    {
        $this->school = School::firstOrFail();
        
        $this->name = $this->school->name;
        $this->institutional_code = $this->school->institutional_code;
        $this->address = $this->school->address ?? '';
        $this->principal_name = $this->school->principal_name ?? '';
        $this->email = $this->school->email ?? '';
        $this->phone = $this->school->phone ?? '';
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institutional_code' => ['required', 'string', 'unique:schools,institutional_code,' . $this->school->id],
            'address' => ['required', 'string'],
            'principal_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'unique:schools,email,' . $this->school->id],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function save(UpdateSchoolAction $updateSchool): void
    {
        $validated = $this->validate();

        $updateSchool->execute($this->school, $validated);

        session()->flash('success', 'School profile updated successfully.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.admin.school.school-profile');
    }
}
