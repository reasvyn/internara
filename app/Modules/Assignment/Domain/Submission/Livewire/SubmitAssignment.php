<?php

declare(strict_types=1);

namespace App\Modules\Assignment\Domain\Submission\Livewire;

use App\Modules\Assignment\Models\Assignment;
use App\Modules\Assignment\Domain\Submission\Actions\SubmitAssignmentAction;
use App\Modules\Assignment\Domain\Submission\Data\SubmitAssignmentData;
use App\Modules\Assignment\Domain\Submission\Models\Submission;
use App\Modules\Core\Livewire\BaseFormView;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\WithFileUploads;
use TallStackUi\Traits\Interactions;

class SubmitAssignment extends BaseFormView
{
    use Interactions;
    use WithFileUploads;

    public ?string $assignmentId = null;

    public string $content = '';

    public $file = null;

    public bool $showDetail = false;

    public ?Assignment $selectedAssignment = null;

    public function viewDetail(Assignment $assignment): void
    {
        $this->selectedAssignment = $assignment->load(['document']);
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
            $this->toast()->success(__('submission.submitted_success'))->send();
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
