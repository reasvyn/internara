<?php

declare(strict_types=1);

namespace App\Modules\Assessment\Livewire;

use App\Modules\Assessment\Actions\InitializeAssessmentAction;
use App\Modules\Assessment\Actions\SubmitPartnerAssessmentAction;
use App\Modules\Assessment\Data\SubmitPartnerAssessmentData;
use App\Modules\Assessment\Domain\Rubric\Models\Rubric;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Assessment\Models\AssessmentMagicLink;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\Attendance\Models\Attendance;
use App\Modules\Journal\Domain\Logbook\Models\Logbook;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\UI\Livewire\BaseFormView;
use App\Modules\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

#[Layout('ui::layouts.guest')]
class PartnerAssessmentPage extends BaseFormView
{
    use Interactions;

    public string $token = '';

    public string $evaluatorName = '';

    public string $evaluatorPhone = '';

    public string $feedback = '';

    public array $scores = [];

    public string $activeTab = 'assessment';

    public bool $isSubmitted = false;

    public bool $isExpired = false;

    public bool $isNotFound = false;

    public function mount(string $token, InitializeAssessmentAction $initAction): void
    {
        $this->token = $token;

        $link = AssessmentMagicLink::where('token', $token)->first();

        if ($link === null) {
            $this->isNotFound = true;

            return;
        }

        if ($link->isExpired()) {
            $this->isExpired = true;
        }

        if ($link->isSubmitted()) {
            $this->isSubmitted = true;
            $this->evaluatorName = $link->evaluator_name ?? '';
            $this->evaluatorPhone = $link->evaluator_phone ?? '';
            $this->feedback = $link->feedback ?? '';
        }

        $initResult = $initAction->execute($link->registration_id);
        $assessment = $initResult['assessment'] ?? Assessment::where('registration_id', $link->registration_id)->first();

        if ($assessment !== null) {
            $content = $assessment->scores_data ?? [];
            $competencies = $content['competencies'] ?? [];
            foreach ($competencies as $key => $compData) {
                if (! is_array($compData)) {
                    continue;
                }
                $compId = $compData['id'] ?? (is_string($key) ? $key : null);
                if ($compId === null) {
                    continue;
                }
                foreach ($compData['indicators'] ?? [] as $indId => $score) {
                    $this->scores[$compId][$indId] = (string) $score;
                }
            }
        }
    }

    #[Computed]
    public function magicLink(): ?AssessmentMagicLink
    {
        return AssessmentMagicLink::with([
            'registration.student.profile',
            'registration.placement.company',
            'registration.internship',
        ])->where('token', $this->token)->first();
    }

    #[Computed]
    public function registration(): ?Registration
    {
        return $this->magicLink?->registration;
    }

    #[Computed]
    public function student(): ?User
    {
        return $this->registration?->student;
    }

    #[Computed]
    public function placement(): ?Placement
    {
        return $this->registration?->placement;
    }

    #[Computed]
    public function company(): ?Company
    {
        return $this->placement?->company;
    }

    #[Computed]
    public function rubric(): ?Rubric
    {
        if ($this->registration === null) {
            return null;
        }

        return Rubric::where(function ($query): void {
            $query->where('internship_id', $this->registration->internship_id)
                ->orWhereNull('internship_id');
        })
            ->where('is_active', true)
            ->orderByRaw('internship_id IS NULL')
            ->first();
    }

    #[Computed]
    public function competencies(): Collection
    {
        $rubric = $this->rubric;
        if ($rubric === null) {
            return new Collection;
        }

        $competencies = collect($rubric->competencies)
            ->filter(function (object $competency) {
                $role = $competency->evaluator_role->value;

                return in_array($role, ['supervisor', 'teacher'], true);
            })
            ->values();

        return new Collection($competencies->all());
    }

    #[Computed]
    public function journals(): Collection
    {
        if ($this->registration === null) {
            return new Collection;
        }

        return Logbook::where('registration_id', $this->registration->id)
            ->orderBy('date', 'desc')
            ->get();
    }

    #[Computed]
    public function attendances(): Collection
    {
        if ($this->registration === null) {
            return new Collection;
        }

        return Attendance::where('registration_id', $this->registration->id)
            ->orderBy('date', 'desc')
            ->get();
    }

    #[Computed]
    public function attendanceStats(): array
    {
        $attendances = $this->attendances;

        return [
            'present' => $attendances->where('status.value', 'present')->count(),
            'late' => $attendances->where('status.value', 'late')->count(),
            'sick' => $attendances->where('status.value', 'sick')->count(),
            'permit' => $attendances->where('status.value', 'permit')->count(),
            'absent' => $attendances->where('status.value', 'absent')->count(),
            'total' => $attendances->count(),
        ];
    }

    public function submit(SubmitPartnerAssessmentAction $action): void
    {
        if ($this->isExpired || $this->isSubmitted) {
            return;
        }

        $this->validate([
            'evaluatorName' => 'required|string|min:3|max:100',
            'evaluatorPhone' => 'nullable|string|max:30',
            'feedback' => 'nullable|string|max:1000',
        ]);

        try {
            $action->execute(new SubmitPartnerAssessmentData(
                token: $this->token,
                evaluatorName: $this->evaluatorName,
                evaluatorPhone: $this->evaluatorPhone,
                feedback: $this->feedback,
                scores: $this->scores,
            ));

            $this->isSubmitted = true;
            $this->toast()->success(__('assessment.submit_success'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
    }

    public function render(): View
    {
        return view('assessment::partner-assessment-page');
    }
}
