<?php

declare(strict_types=1);

namespace App\Guidance\SupervisionLog\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Guidance\SupervisionLog\Actions\CreateLogAction;
use App\Guidance\SupervisionLog\Actions\DeleteLogAction;
use App\Guidance\SupervisionLog\Models\SupervisionLog;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Layout;

class StudentLogManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showConfirm = false;

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
            ['key' => 'date', 'label' => __('guidance.date'), 'sortable' => true],
            ['key' => 'topic', 'label' => __('guidance.topic')],
            ['key' => 'status', 'label' => __('guidance.status')],
            ['key' => 'supervisor_feedback', 'label' => __('guidance.feedback')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
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
            flash()->error(__('guidance.no_active_registration'));

            return;
        }

        $action->execute($user, $registration->id, [
            'supervisor_id' => $this->supervisorId,
            'date' => $this->date,
            'topic' => $this->topic,
            'notes' => $this->notes,
        ]);

        flash()->success(__('guidance.log_created'));
        $this->showModal = false;
    }

    public function askDelete(string $id): void
    {
        $this->confirmTarget = $id;
        $this->confirmType = 'delete';
        $this->showConfirm = true;
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
            flash()->success(__('guidance.log_deleted'));
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('guidance.supervision-log.student-log-manager');
    }
}
