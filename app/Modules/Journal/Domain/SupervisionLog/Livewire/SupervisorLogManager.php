<?php

declare(strict_types=1);

namespace App\Modules\Journal\Domain\SupervisionLog\Livewire;

use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Journal\Domain\SupervisionLog\Actions\CreateSupervisionLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Actions\VerifySupervisionLogAction;
use App\Modules\Journal\Domain\SupervisionLog\Data\CreateSupervisionLogData;
use App\Modules\Journal\Domain\SupervisionLog\Models\SupervisionLog;
use App\Modules\UI\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class SupervisorLogManager extends BaseRecordManager
{
    public bool $showModal = false;

    public string $registrationId = '';

    public string $date = '';

    public string $topic = '';

    public string $notes = '';

    public string $type = 'guidance';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    public function headers(): array
    {
        return [
            ['index' => 'date', 'label' => __('journals.date'), 'sortable' => true],
            ['index' => 'registration.student.name', 'label' => __('journals.student')],
            ['index' => 'type', 'label' => __('journals.supervision.type')],
            ['index' => 'topic', 'label' => __('journals.topic'), 'sortable' => true],
            ['index' => 'status', 'label' => __('journals.status')],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return SupervisionLog::query()
            ->where('supervisor_id', auth()->id())
            ->with(['registration.student']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('topic', 'like', "%{$this->search}%")
                ->orWhereHas('registration.student', fn ($s) => $s->where('name', 'like', "%{$this->search}%"));
        });
    }

    #[Computed]
    public function students()
    {
        return Registration::query()
            ->with(['mentee'])
            ->whereHas('mentors', fn ($q) => $q->where('user_id', auth()->id()))
            ->get();
    }

    public function create(): void
    {
        $this->authorize('create', SupervisionLog::class);

        $this->reset(['registrationId', 'topic', 'notes']);
        $this->date = now()->toDateString();
        $this->showModal = true;
    }

    public function save(CreateSupervisionLogAction $createAction): void
    {
        $this->authorize('create', SupervisionLog::class);

        $this->validate([
            'registrationId' => 'required|exists:registrations,id',
            'date' => 'required|date',
            'topic' => 'required|string|max:255',
            'notes' => 'required|string',
        ]);

        $createAction->execute(new CreateSupervisionLogData(
            userId: auth()->id(),
            registrationId: $this->registrationId,
            data: [
                'date' => $this->date,
                'topic' => $this->topic,
                'notes' => $this->notes,
            ],
        ));

        $this->showModal = false;
        $this->toast()->success(__('journal.log_recorded'))->send();
    }

    public function verify(SupervisionLog $log, VerifySupervisionLogAction $verifyAction): void
    {
        $this->authorize('update', $log);

        $verifyAction->execute($log, auth()->user());
        $this->toast()->success(__('journal.log_verified'))->send();
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('journal.supervision-log.log-manager', [
            'logs' => $this->rows(),
        ]);
    }
}
