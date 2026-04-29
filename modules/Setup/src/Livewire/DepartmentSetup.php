<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Modules\Department\Models\Department;
use Modules\Department\Services\Contracts\DepartmentService;

/**
 * Department Setup step
 *
 * [S1 - Secure] Validated input, authorization
 * [S2 - Sustain] Clear form handling
 * [S3 - Scalable] UUID-based, service contract
 */
class DepartmentSetup extends SetupWizardBase
{
    public string $name = '';
    public string $code = '';
    public string $description = '';

    public function mount(): void
    {
        $this->authorizeStepAccess('department');
        $this->ensureNotInstalled();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'unique:departments,code'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('setup::validation.department.name_required'),
            'code.required' => __('setup::validation.department.code_required'),
            'code.unique' => __('setup::validation.department.code_taken'),
            'description.max' => __('setup::validation.department.description_max'),
        ];
    }

    public function saveDepartment(DepartmentService $departmentService): void
    {
        $validated = $this->validate();

        $department = $departmentService->create($validated);

        $this->setupService->completeStep('department', [
            'department_id' => $department->id,
        ]);

        $token = request()->get('token') ?? session('setup_token');
        
        $this->redirect(route('setup.internship', ['token' => $token]));
    }

    public function render()
    {
        return view('setup::livewire.department-setup', [
            'progress' => $this->progress,
        ]);
    }
}
