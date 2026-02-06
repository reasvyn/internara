<?php

declare(strict_types=1);

namespace Modules\Assignment\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Assignment\Models\Assignment;
use Modules\Assignment\Services\Contracts\AssignmentService;
use Modules\Assignment\Services\Contracts\SubmissionService;
use Modules\Internship\Services\Contracts\RegistrationService;

/**
 * Class AssignmentSubmission
 *
 * Dynamic UI for students to view and submit their assignments.
 */
class AssignmentSubmission extends Component
{
    use WithFileUploads;

    /**
     * Store uploaded files keyed by assignment ID.
     */
    public array $uploads = [];

    /**
     * Store text content keyed by assignment ID.
     */
    public array $contents = [];

    /**
     * The student's active registration ID.
     */
    public ?string $registrationId = null;

    /**
     * Mount the component.
     */
    public function mount(RegistrationService $regService): void
    {
        $registration = $regService->first([
            'student_id' => Auth::id(),
            'latest_status' => 'active',
        ]);

        if ($registration) {
            $this->registrationId = $registration->id;
        }
    }

    /**
     * Submit an assignment.
     */
    public function submit(string $assignmentId, SubmissionService $service): void
    {
        $assignment = Assignment::findOrFail($assignmentId);

        $rules = [];
        if (
            $assignment->type->slug === 'laporan-pkl' ||
            $assignment->type->slug === 'presentasi-pkl'
        ) {
            $rules["uploads.{$assignmentId}"] = 'required|file|max:20480';
        } else {
            $rules["contents.{$assignmentId}"] = 'required|string';
        }

        $this->validate($rules);

        try {
            $content = $this->uploads[$assignmentId] ?? $this->contents[$assignmentId];

            $service->submit($this->registrationId, $assignmentId, $content);

            notify(__('assignment::ui.success_submitted'), 'success');

            // Reset input
            unset($this->uploads[$assignmentId], $this->contents[$assignmentId]);
        } catch (\Throwable $e) {
            notify($e->getMessage(), 'error');
        }
    }

    /**
     * Get the assignments for the active program.
     */
    public function getAssignmentsProperty(AssignmentService $service)
    {
        if (! $this->registrationId) {
            return collect();
        }

        $registration = app(RegistrationService::class)->find($this->registrationId);

        return $service->get(['internship_id' => $registration->internship_id]);
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('assignment::livewire.assignment-submission');
    }
}