<?php

declare(strict_types=1);

namespace App\Journals\SupervisionLog\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Journals\SupervisionLog\Actions\CreateLogAction;
use App\Journals\SupervisionLog\Actions\DeleteLogAction;
use App\Journals\SupervisionLog\Data\CreateLogData;
use App\Journals\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;
use TallStackUi\Traits\Interactions;

class StudentLogManager extends BaseRecordManager
{
    use Interactions;

    public bool $showModal = false;

    public string $confirmType = '';

    public ?string $confirmTarget = null;

    public string $supervisorId = '';

    public string $date = '';

    public string $topic = '';

    public string $notes = '';

    public Collection $supervisors;

    public function mount(): void
    {
        $this->supervisors = User::whereHas('roles', fn ($q) => $q->whereIn('name', ['teacher', 'supervisor']))->get();
    }

    public function headers(): array
    {
        return [
            ['index' => 'date', 'label' => __('journals.date'), 'sortable' => true],
            ['index' => 'topic', 'label' => __('journals.topic')],
            ['index' => 'status', 'label' => __('journals.status')],
            ['index' => 'supervisor_feedback', 'label' => __('journals.feedback')],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        $user = auth()->user();
        $registration = $user->registrations()->where('status', 'active')->first();

        return SupervisionLog::query()
            ->where('registration_id', $registration?->id)
            ->latest('date');
    }

    public function create(): void
    {
        $this->authorize('create', SupervisionLog::class);
        $this->resetErrorBag();
        $this->supervisorId = '';
        $this->date = now()->toDateString();
        $this->topic = '';
        $this->notes = '';
        $this->showModal = true;
    }

    public function save(CreateLogAction $action): void
    {
        $this->authorize('create', SupervisionLog::class);

        $this->validate([
            'supervisorId' => 'required|exists:users,id',
            'date' => 'required|date',
            'topic' => 'required|string|max:255',
            'notes' => 'required|string',
        ]);

        $user = auth()->user();
        $registration = $user->registrations()->where('status', 'active')->first();

        if (! $registration) {
            $this->toast()->error(__('journals.no_active_registration'))->send();

            return;
        }

        $action->execute(new CreateLogData(
            studentId: $user->id,
            registrationId: $registration->id,
            data: [
                'supervisor_id' => $this->supervisorId,
                'date' => $this->date,
                'topic' => $this->topic,
                'notes' => $this->notes,
            ],
        ));

        $this->toast()->success(__('journals.log_created'))->send();
        $this->showModal = false;
    }

    public function askDelete(string $id): void
    {
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(DeleteLogAction $action): void
    {
        if ($this->confirmTarget === null) {
            return;
        }

        try {
            $log = SupervisionLog::findOrFail($this->confirmTarget);
            $this->authorize('delete', $log);
            $action->execute($log);
            $this->toast()->success(__('journals.log_deleted'))->send();
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('journals.supervision-log.student-log-manager');
    }
}
