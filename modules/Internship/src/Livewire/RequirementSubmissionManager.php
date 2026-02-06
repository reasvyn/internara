<?php

declare(strict_types=1);

namespace Modules\Internship\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Internship\Enums\RequirementType;
use Modules\Internship\Models\InternshipRegistration;
use Modules\Internship\Services\Contracts\InternshipRequirementService;

class RequirementSubmissionManager extends Component
{
    use WithFileUploads;

    public ?string $registrationId = null;

    public array $files = [];

    public array $values = [];

    /**
     * Initialize the component.
     */
    public function mount(?string $registrationId = null): void
    {
        if (! $registrationId) {
            $registration = app(
                \Modules\Internship\Services\Contracts\RegistrationService::class,
            )->first([
                'student_id' => auth()->id(),
            ]);
            $registrationId = $registration?->id;
        }

        if ($registrationId) {
            $this->registrationId = $registrationId;
            $this->loadData();
        }
    }

    /**
     * Load requirements and existing submissions.
     */
    public function loadData(): void
    {
        $registration = InternshipRegistration::findOrFail($this->registrationId);

        // Ensure user is authorized (student owning the registration or admin)
        // For now, using basic check, in production use Policies
        if (
            auth()->id() !== $registration->student_id &&
            ! auth()->user()->can('internship.update')
        ) {
            abort(403);
        }

        $requirements = app(InternshipRequirementService::class)->getActiveForYear(
            $registration->academic_year,
        );
        $submissions = $registration->requirementSubmissions()->get()->keyBy('requirement_id');

        foreach ($requirements as $requirement) {
            if (isset($submissions[$requirement->id])) {
                $this->values[$requirement->id] = $submissions[$requirement->id]->value;
            } else {
                $this->values[$requirement->id] =
                    $requirement->type === RequirementType::CONDITION ? '0' : '';
            }
        }
    }

    /**
     * Submit a requirement.
     */
    public function submit(string $requirementId): void
    {
        $requirementService = app(InternshipRequirementService::class);
        $requirement = $requirementService->find($requirementId);

        $file = $this->files[$requirementId] ?? null;
        $value = $this->values[$requirementId] ?? null;

        try {
            $requirementService->submit($this->registrationId, $requirementId, $value, $file);

            notify(__('internship::ui.requirement_submitted'), 'success');
            $this->loadData();
        } catch (\Throwable $e) {
            notify($e->getMessage(), 'error');
        }
    }

    public function render()
    {
        $registration = InternshipRegistration::with(
            'requirementSubmissions.requirement',
        )->findOrFail($this->registrationId);
        $requirements = app(InternshipRequirementService::class)->getActiveForYear(
            $registration->academic_year,
        );
        $submissions = $registration->requirementSubmissions()->get()->keyBy('requirement_id');

        return view('internship::livewire.requirement-submission-manager', [
            'requirements' => $requirements,
            'submissions' => $submissions,
            'registration' => $registration,
        ]);
    }
}