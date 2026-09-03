<?php

declare(strict_types=1);

namespace App\Modules\Journals\Domain\MonitoringVisit\Livewire;

use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Livewire\BaseRecordManager;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journals\Domain\MonitoringVisit\Actions\CreateVisitAction;
use App\Modules\Journals\Domain\MonitoringVisit\Actions\VerifyVisitAction;
use App\Modules\Journals\Domain\MonitoringVisit\Data\CreateVisitData;
use App\Modules\Journals\Domain\MonitoringVisit\Enums\VisitMethod;
use App\Modules\Journals\Domain\MonitoringVisit\Models\MonitoringVisit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class VisitManager extends BaseRecordManager
{
    use Interactions;

    public bool $showModal = false;

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public string $registrationId = '';

    public string $visitDate = '';

    public string $method = '';

    public string $location = '';

    public ?int $durationMinutes = null;

    public string $notes = '';

    public string $studentCondition = '';

    public string $companyFeedback = '';

    public string $followUpActions = '';

    public function boot(): void
    {
        $this->authorize('viewAny', MonitoringVisit::class);
    }

    public function headers(): array
    {
        return [
            ['index' => 'visit_date', 'label' => __('journals.visit_date'), 'sortable' => true],
            ['index' => 'teacher.name', 'label' => __('journals.teacher')],
            ['index' => 'method', 'label' => __('journals.method')],
            ['index' => 'location', 'label' => __('journals.location')],
            ['index' => 'is_verified', 'label' => __('journals.status')],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function applySearch(Builder $query): Builder
    {
        $term = '%'.$this->search.'%';

        return $query->where(function (Builder $q) use ($term) {
            $q->where('location', 'like', $term)
                ->orWhere('notes', 'like', $term)
                ->orWhereHas('teacher', fn (Builder $t) => $t->where('name', 'like', $term));
        });
    }

    protected function query(): Builder
    {
        $user = auth()->user();

        return MonitoringVisit::query()
            ->with(['teacher', 'registration.student'])
            ->when(
                $user && ! $user->hasAnyRole(['super_admin', 'admin']),
                fn ($q) => $q->where('teacher_id', $user->id),
            );
    }

    #[Computed]
    public function methodOptions(): array
    {
        return collect(VisitMethod::cases())
            ->map(fn ($m) => ['id' => $m->value, 'name' => $m->label()])
            ->toArray();
    }

    #[Computed]
    public function students()
    {
        return Registration::query()
            ->whereHas('mentors', fn ($q) => $q->where('user_id', auth()->id()))
            ->with('student')
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', MonitoringVisit::class);
        $this->resetErrorBag();
        $this->registrationId = '';
        $this->visitDate = now()->toDateString();
        $this->method = '';
        $this->location = '';
        $this->durationMinutes = null;
        $this->notes = '';
        $this->studentCondition = '';
        $this->companyFeedback = '';
        $this->followUpActions = '';
        $this->showModal = true;
    }

    public function save(CreateVisitAction $action): void
    {
        $this->authorize('create', MonitoringVisit::class);

        $this->validate([
            'registrationId' => 'required|exists:registrations,id',
            'visitDate' => 'required|date',
            'method' => 'required|string|in:site_visit,virtual_meeting,phone_call',
            'location' => 'nullable|string|max:512',
            'durationMinutes' => 'nullable|integer|min:1|max:1440',
            'notes' => 'nullable|string',
        ]);

        $action->execute(new CreateVisitData(
            teacherId: auth()->id(),
            registrationId: $this->registrationId,
            data: [
                'visit_date' => $this->visitDate,
                'method' => $this->method,
                'location' => $this->location,
                'duration_minutes' => $this->durationMinutes,
                'notes' => $this->notes,
                'student_condition' => $this->studentCondition ?: null,
                'company_feedback' => $this->companyFeedback ?: null,
                'follow_up_actions' => $this->followUpActions ?: null,
            ],
        ));

        $this->toast()->success(__('journals.visit_created'))->send();
        $this->showModal = false;
    }

    public function askVerify(string $id): void
    {
        $this->confirmTarget = $id;
        $this->confirmType = 'verify';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(VerifyVisitAction $verifyAction): void
    {
        if ($this->confirmTarget === null) {
            return;
        }

        try {
            match ($this->confirmType) {
                'verify' => $this->executeVerify($this->confirmTarget, $verifyAction),
                default => null,
            };
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
    }

    private function executeVerify(string $id, VerifyVisitAction $action): void
    {
        $visit = MonitoringVisit::findOrFail($id);
        $this->authorize('verify', MonitoringVisit::class);
        $action->execute($visit, auth()->user());
        $this->toast()->success(__('journals.visit_verified'))->send();
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('journals.monitoring-visit.visit-manager');
    }
}
