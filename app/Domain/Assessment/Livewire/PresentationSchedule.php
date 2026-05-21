<?php

declare(strict_types=1);

namespace App\Domain\Assessment\Livewire;

use App\Domain\Assessment\Actions\SchedulePresentationAction;
use App\Domain\Assessment\Actions\ScorePresentationAction;
use App\Domain\Assessment\Enums\PresentationStatus;
use App\Domain\Assessment\Models\Presentation;
use App\Domain\Assessment\Models\PresentationExaminer;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class PresentationSchedule extends BaseRecordManager
{
    public bool $showScheduleModal = false;

    public bool $showScoreModal = false;

    public ?string $scoringExaminerId = null;

    public array $scheduleData = [
        'registration_id' => '',
        'scheduled_at' => '',
        'location' => '',
        'examiner_ids' => [],
        'notes' => '',
    ];

    public array $scoreData = [
        'score' => null,
        'feedback' => '',
    ];

    public function headers(): array
    {
        return [
            ['key' => 'scheduled_at', 'label' => __('presentation.scheduled_at'), 'sortable' => true],
            ['key' => 'student_name', 'label' => __('presentation.student'), 'sortable' => true],
            ['key' => 'status', 'label' => __('presentation.status'), 'sortable' => true],
            ['key' => 'presentation_score', 'label' => __('presentation.presentation_score')],
            ['key' => 'final_score', 'label' => __('presentation.final_score')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Presentation::query()
            ->select(['presentations.*', 'users.name as student_name'])
            ->join('registrations', 'presentations.registration_id', '=', 'registrations.id')
            ->join('mentees', 'registrations.mentee_id', '=', 'mentees.id')
            ->join('users', 'mentees.user_id', '=', 'users.id');
    }

    #[Computed]
    public function teachers(): array
    {
        return User::query()
            ->role(['super_admin', 'admin', 'teacher'])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->toArray();
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->scheduleData = [
            'registration_id' => '',
            'scheduled_at' => '',
            'location' => '',
            'examiner_ids' => [],
            'notes' => '',
        ];
        $this->showScheduleModal = true;
    }

    public function saveSchedule(SchedulePresentationAction $action): void
    {
        $this->validate([
            'scheduleData.registration_id' => ['required', 'exists:registrations,id'],
            'scheduleData.scheduled_at' => ['required', 'date'],
            'scheduleData.examiner_ids' => ['required', 'array', 'min:1', 'max:5'],
            'scheduleData.examiner_ids.*' => ['exists:users,id'],
        ]);

        $action->execute($this->scheduleData);
        flash()->success(__('presentation.schedule_success'));
        $this->showScheduleModal = false;
    }

    public function score(PresentationExaminer $examiner): void
    {
        $this->scoringExaminerId = $examiner->id;
        $this->scoreData = ['score' => $examiner->score, 'feedback' => $examiner->feedback ?? ''];
        $this->showScoreModal = true;
    }

    public function saveScore(ScorePresentationAction $action): void
    {
        $this->validate([
            'scoreData.score' => ['required', 'numeric', 'min:0', 'max:100'],
            'scoreData.feedback' => ['nullable', 'string', 'max:2000'],
        ]);

        $examiner = PresentationExaminer::findOrFail($this->scoringExaminerId);
        $action->execute($examiner, $this->scoreData);
        flash()->success(__('presentation.score_success'));
        $this->showScoreModal = false;
    }

    #[Layout('layouts::app')]
    public function render(): View
    {
        return view('assessment.presentation-schedule', [
            'statusOptions' => PresentationStatus::cases(),
        ]);
    }
}
