<?php

declare(strict_types=1);

namespace Modules\Setup\Livewire;

use Modules\Internship\Models\Internship;
use Modules\Internship\Services\Contracts\InternshipService;

/**
 * Internship Program Setup step
 *
 * [S1 - Secure] Date validation, authorization
 * [S2 - Sustain] Clear date handling
 * [S3 - Scalable] UUID-based, service contract
 */
class InternshipSetup extends SetupWizardBase
{
    public string $name = '';

    public string $startDate = '';

    public string $endDate = '';

    public string $description = '';

    public function mount(): void
    {
        $this->authorizeStepAccess('internship');
        $this->ensureNotInstalled();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'startDate' => ['required', 'date', 'after:today'],
            'endDate' => ['required', 'date', 'after:startDate'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('setup::validation.internship.name_required'),
            'startDate.required' => __('setup::validation.internship.start_required'),
            'startDate.after' => __('setup::validation.internship.start_future'),
            'endDate.required' => __('setup::validation.internship.end_required'),
            'endDate.after' => __('setup::validation.internship.end_after_start'),
        ];
    }

    public function saveInternship(InternshipService $internshipService): void
    {
        $validated = $this->validate();

        $internship = $internshipService->create([
            'name' => $validated['name'],
            'start_date' => $validated['startDate'],
            'end_date' => $validated['endDate'],
            'description' => $validated['description'] ?? null,
            'status' => 'draft',
        ]);

        $this->setupService->completeStep('internship', [
            'internship_id' => $internship->id,
        ]);

        $token = request()->get('token') ?? session('setup_token');

        $this->redirect(route('setup.complete', ['token' => $token]));
    }

    public function render()
    {
        return view('setup::livewire.internship-setup', [
            'progress' => $this->progress,
        ]);
    }
}
