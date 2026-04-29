<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Illuminate\Support\Facades\Validator;
use Modules\School\Models\School;
use Modules\School\Services\Contracts\SchoolService;

/**
 * School setup step
 *
 * [S1 - Secure] Validated input, authorization
 * [S2 - Sustain] Clear form handling
 * [S3 - Scalable] UUID-based, service contract usage
 */
class SchoolSetup extends SetupWizardBase
{
    public string $name = '';
    public string $type = '';
    public string $address = '';
    public string $phone = '';
    public string $email = '';
    public string $website = '';

    public function mount(): void
    {
        $this->authorizeStepAccess('school');
        $this->ensureNotInstalled();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:university,polytechnic,school,college'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('setup::validation.school.name_required'),
            'type.required' => __('setup::validation.school.type_required'),
            'address.required' => __('setup::validation.school.address_required'),
            'phone.required' => __('setup::validation.school.phone_required'),
            'email.required' => __('setup::validation.school.email_required'),
            'email.email' => __('setup::validation.school.email_invalid'),
        ];
    }

    public function saveSchool(SchoolService $schoolService): void
    {
        $validated = Validator::make([
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
        ], $this->rules(), $this->messages())->validate();

        $school = $schoolService->create($validated);

        $this->setupService->completeStep('school', [
            'school_id' => $school->id,
        ]);

        $token = request()->get('token') ?? session('setup_token');
        
        $this->redirect(route('setup.account', ['token' => $token]));
    }

    public function render()
    {
        return view('setup::livewire.school-setup', [
            'progress' => $this->progress,
        ]);
    }
}
