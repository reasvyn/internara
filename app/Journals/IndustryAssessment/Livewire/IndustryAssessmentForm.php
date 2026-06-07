<?php

declare(strict_types=1);

namespace App\Journals\IndustryAssessment\Livewire;

use App\Enrollment\Models\Registration;
use App\Guidance\Mentor\Models\Mentor;
use App\Journals\IndustryAssessment\Actions\SubmitIndustryAssessmentAction;
use App\Journals\IndustryAssessment\Models\IndustryAssessment;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class IndustryAssessmentForm extends Component
{
    public ?string $registrationId = null;

    public ?float $score = null;

    public string $notes = '';

    /** @var array<int, array{criterion: string, weight: numeric, score: numeric}> */
    public array $rubric = [];

    public bool $showForm = false;

    public function boot(): void
    {
        if (! auth()->user()?->hasRole('supervisor')) {
            abort(403);
        }
    }

    public function mount(): void
    {
        $this->loadAssessment();
    }

    public function loadAssessment(): void
    {
        $user = auth()->user();

        $registrations = Registration::query()
            ->whereHas(
                'mentors',
                fn ($q) => $q
                    ->where('user_id', $user->id)
                    ->where('type', Mentor::TYPE_INDUSTRY_SUPERVISOR),
            )
            ->where('status', 'active')
            ->get();

        if ($registrations->count() === 1) {
            $this->registrationId = $registrations->first()->id;
            $this->loadExisting();
        }
    }

    public function selectRegistration(string $id): void
    {
        $this->registrationId = $id;
        $this->loadExisting();
    }

    public function loadExisting(): void
    {
        if (! $this->registrationId) {
            return;
        }

        $existing = IndustryAssessment::query()
            ->where('registration_id', $this->registrationId)
            ->where('supervisor_id', auth()->id())
            ->first();

        if ($existing) {
            $this->score = $existing->score ? (float) $existing->score : null;
            $this->notes = $existing->notes ?? '';
            $this->rubric = $existing->rubric_data ?? [];
            $this->showForm = true;
        } else {
            $this->score = null;
            $this->notes = '';
            $this->rubric = [];
            $this->showForm = true;
        }
    }

    public function addCriterion(): void
    {
        $this->rubric[] = ['criterion' => '', 'weight' => 0, 'score' => 0];
    }

    public function removeCriterion(int $index): void
    {
        unset($this->rubric[$index]);
        $this->rubric = array_values($this->rubric);
    }

    public function save(SubmitIndustryAssessmentAction $action): void
    {
        $this->validate([
            'registrationId' => 'required|exists:registrations,id',
            'rubric' => 'nullable|array',
            'rubric.*.criterion' => 'required|string|max:255',
            'rubric.*.weight' => 'required|numeric|min:0|max:100',
            'rubric.*.score' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:5000',
        ]);

        $registration = Registration::findOrFail($this->registrationId);

        $action->execute(
            $registration,
            auth()->user(),
            $this->rubric !== [] ? $this->rubric : null,
            $this->notes ?: null,
        );

        flash()->success(__('logbook.assessment_saved'));
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $user = auth()->user();

        $registrations = Registration::query()
            ->with('mentee.user')
            ->whereHas(
                'mentors',
                fn ($q) => $q
                    ->where('user_id', $user->id)
                    ->where('type', Mentor::TYPE_INDUSTRY_SUPERVISOR),
            )
            ->where('status', 'active')
            ->get();

        $assessments = IndustryAssessment::query()
            ->with('registration.mentee.user')
            ->where('supervisor_id', $user->id)
            ->get()
            ->keyBy('registration_id');

        return view('journals.industry-assessment.industry-assessment-form', [
            'registrations' => $registrations,
            'assessments' => $assessments,
        ]);
    }
}
