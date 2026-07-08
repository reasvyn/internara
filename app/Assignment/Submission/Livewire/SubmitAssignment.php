<?php

declare(strict_types=1);

namespace App\Assignment\Submission\Livewire;

use App\Assignment\Models\Assignment;
use App\Assignment\Submission\Actions\SubmitAssignmentAction;
use App\Assignment\Submission\Data\SubmitAssignmentData;
use App\Assignment\Submission\Models\Submission;
use App\Core\Livewire\BaseFormView;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\WithFileUploads;

class SubmitAssignment extends BaseFormView
{
    use WithFileUploads;

    public ?string $assignmentId = null;

    public string $content = '';

    public $file = null;

    public bool $showDetail = false;

    public ?Assignment $selectedAssignment = null;

    public function viewDetail(Assignment $assignment): void
    {
        $this->selectedAssignment = $assignment->load(['type', 'document']);
        $this->assignmentId = $assignment->id;
        $this->showDetail = true;
    }

    public function back(): void
    {
        $this->showDetail = false;
        $this->selectedAssignment = null;
    }

    public function submit(SubmitAssignmentAction $action): void
    {
        $this->authorize('create', Submission::class);

        $this->validate([
            'content' => 'required|string|min:20',
            'file' => 'nullable|file|mimes:pdf,doc,docx,zip,ppt,pptx|max:10240',
        ]);

        $assignment = Assignment::findOrFail($this->assignmentId);

        $this->handleSave(function () use ($action, $assignment) {
            $action->execute(
                student: Auth::user(),
                assignment: $assignment,
                data: new SubmitAssignmentData(content: $this->content),
            );

            $this->reset(['content', 'file', 'assignmentId']);
            $this->showDetail = false;
            flash()->success('Assignment submitted successfully.');
        });
    }

    public function render(): View
    {
        $studentId = Auth::id();
        $registration = Auth::user()->getActiveRegistration();

        if (! $registration) {
            return view('assignment.submission.submit-assignment', [
                'assignments' => collect(),
                'submissions' => collect(),
            ]);
        }

        $assignments = Assignment::where('internship_id', $registration->internship_id)
            ->where('status', 'published')
            ->with([
                'type',
                'document',
                'submissions' => fn ($q) => $q->where('student_id', $studentId),
            ])
            ->get();

        $submissions = Submission::where('student_id', $studentId)->with('assignment')->get();

        return view('assignment.submission.submit-assignment', [
            'assignments' => $assignments,
            'submissions' => $submissions,
        ]);
    }
}
