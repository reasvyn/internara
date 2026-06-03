<?php

declare(strict_types=1);

namespace App\Domain\Assignment\Aggregates\Submission\Livewire;

use App\Domain\Assignment\Aggregates\Assignment\Models\Assignment;
use App\Domain\Assignment\Aggregates\Submission\Actions\GradeSubmissionAction;
use App\Domain\Assignment\Aggregates\Submission\Models\Submission;
use App\Domain\Guidance\Aggregates\Mentor\Models\Mentor;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class SubmissionGrading extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $assignmentFilter = '';

    public ?string $selectedSubmissionId = null;

    public ?Submission $selectedSubmission = null;

    public ?float $score = null;

    public string $feedback = '';

    public string $gradeStatus = 'verified';

    public function boot(): void
    {
        if (! Auth::user()?->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor'])) {
            abort(403);
        }
    }

    public function viewSubmission(string $submissionId): void
    {
        $submission = Submission::with(['student', 'assignment.type', 'registration'])->findOrFail($submissionId);

        $this->selectedSubmissionId = $submission->id;
        $this->selectedSubmission = $submission;
        $this->score = $submission->score ?? 0;
        $this->feedback = $submission->feedback ?? '';
        $this->gradeStatus = $submission->status->value === 'revision_required' ? 'revision_required' : 'verified';
    }

    public function back(): void
    {
        $this->selectedSubmissionId = null;
        $this->selectedSubmission = null;
        $this->reset(['score', 'feedback', 'gradeStatus']);
    }

    public function grade(GradeSubmissionAction $action): void
    {
        $this->validate([
            'score' => 'required|numeric|min:0|max:100',
            'feedback' => 'required|string|min:10',
            'gradeStatus' => 'required|in:verified,revision_required',
        ]);

        $submission = Submission::findOrFail($this->selectedSubmissionId);
        $this->authorize('verify', $submission);

        $action->execute(
            submission: $submission,
            grader: Auth::user(),
            score: $this->score,
            status: $this->gradeStatus,
            feedback: $this->feedback,
        );

        flash()->success('Submission graded successfully.');
        $this->back();
    }

    public function render(): View
    {
        $query = Submission::query()
            ->with(['student', 'assignment.type'])
            ->whereIn('status', ['submitted', 'revision_required']);

        if ($this->search) {
            $query->whereHas('student', fn ($q) => $q->where('name', 'like', "%{$this->search}%"));
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        if ($this->assignmentFilter) {
            $query->where('assignment_id', $this->assignmentFilter);
        }

        $user = Auth::user();
        if ($user->hasRole('teacher')) {
            $query->whereHas('registration', fn ($q) => $q->whereHas('mentors', fn ($mq) => $mq->where('user_id', $user->id)->where('type', Mentor::TYPE_SCHOOL_TEACHER)));
        } elseif ($user->hasRole('supervisor')) {
            $query->whereHas('registration', fn ($q) => $q->whereHas('mentors', fn ($mq) => $mq->where('user_id', $user->id)->where('type', Mentor::TYPE_INDUSTRY_SUPERVISOR)));
        }

        return view('assignment.submission.submission-grading', [
            'submissions' => $query->latest()->paginate(10),
            'assignments' => Assignment::whereHas('submissions', fn ($q) => $q->whereIn('status', ['submitted', 'revision_required']))->get(),
        ]);
    }
}
