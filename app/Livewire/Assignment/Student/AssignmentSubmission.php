<?php

declare(strict_types=1);

namespace App\Livewire\Assignment\Student;

use App\Domain\Assignment\Actions\SubmitAssignmentAction;
use App\Domain\Assignment\Models\Assignment;
use App\Domain\Assignment\Models\Submission;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

/**
 * Student UI for viewing and submitting assignments.
 *
 * S1 - Secure: File upload validation.
 * S2 - Sustain: Clear submission flow.
 */
class AssignmentSubmission extends Component
{
    use WithFileUploads;

    public ?string $assignmentId = null;

    public string $content = '';

    public $file = null;

    public function render(): View
    {
        $studentId = Auth::id();
        $registration = Auth::user()->activeRegistration;

        if (! $registration) {
            return view('livewire.assignment.assignment-submission', [
                'assignments' => collect(),
                'submissions' => collect(),
            ]);
        }

        $assignments = Assignment::where('internship_id', $registration->internship_id)
            ->where('status', 'published')
            ->with(['type', 'submissions' => fn ($query) => $query->where('student_id', $studentId)])
            ->get();

        return view('livewire.assignment.assignment-submission', [
            'assignments' => $assignments,
            'submissions' => Submission::where('student_id', $studentId)->get(),
        ]);
    }

    public function submit(string $assignmentId, SubmitAssignmentAction $action): void
    {
        $this->validate([
            'content' => 'required|string',
            'file' => 'nullable|file|max:10240', // 10MB max
        ]);

        $assignment = Assignment::findOrFail($assignmentId);
        $registration = Auth::user()->activeRegistration;

        if (! $registration) {
            $this->dispatch('swal:error', message: 'No active internship registration.');

            return;
        }

        $mediaPath = null;
        if ($this->file) {
            $mediaPath = $this->file->getRealPath();
        }

        $action->execute(
            assignment: $assignment,
            registrationId: $registration->id,
            studentId: Auth::id(),
            content: $this->content,
            mediaPath: $mediaPath,
        );

        $this->reset(['content', 'file']);
        $this->dispatch('swal:success', message: 'Assignment submitted successfully.');
    }
}
