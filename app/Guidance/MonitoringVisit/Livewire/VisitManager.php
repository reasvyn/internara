<?php

declare(strict_types=1);

namespace App\Guidance\MonitoringVisit\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Enrollment\Registration\Models\Registration;
use App\Guidance\MonitoringVisit\Actions\CreateVisitAction;
use App\Guidance\MonitoringVisit\Actions\VerifyVisitAction;
use App\Guidance\MonitoringVisit\Enums\VisitMethod;
use App\Guidance\MonitoringVisit\Models\MonitoringVisit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class VisitManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showConfirm = false;

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
            ['key' => 'visit_date', 'label' => __('guidance.visit_date'), 'sortable' => true],
            ['key' => 'teacher.name', 'label' => __('guidance.teacher')],
            ['key' => 'method', 'label' => __('guidance.method')],
            ['key' => 'location', 'label' => __('guidance.location')],
            ['key' => 'is_verified', 'label' => __('guidance.status')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
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

        $action->execute(auth()->user(), $this->registrationId, [
            'visit_date' => $this->visitDate,
            'method' => $this->method,
            'location' => $this->location,
            'duration_minutes' => $this->durationMinutes,
            'notes' => $this->notes,
            'student_condition' => $this->studentCondition ?: null,
            'company_feedback' => $this->companyFeedback ?: null,
            'follow_up_actions' => $this->followUpActions ?: null,
        ]);

        flash()->success(__('guidance.visit_created'));
        $this->showModal = false;
    }

    public function askVerify(string $id): void
    {
        $this->confirmTarget = $id;
        $this->confirmType = 'verify';
        $this->showConfirm = true;
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
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
    }

    private function executeVerify(string $id, VerifyVisitAction $action): void
    {
        $visit = MonitoringVisit::findOrFail($id);
        $this->authorize('verify', MonitoringVisit::class);
        $action->execute($visit, auth()->user());
        flash()->success(__('guidance.visit_verified'));
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('guidance.monitoring-visit.visit-manager');
    }
}
