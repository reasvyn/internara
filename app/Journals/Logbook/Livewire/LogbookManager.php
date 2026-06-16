<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Livewire;

use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Journals\Logbook\Actions\CreateLogbookAction;
use App\Journals\Logbook\Actions\DeleteLogbookAction;
use App\Journals\Logbook\Actions\UpdateLogbookAction;
use App\Journals\Logbook\Livewire\Forms\LogbookForm;
use App\Journals\Logbook\Models\Logbook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class LogbookManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showSupervisorNoteModal = false;

    public bool $showConfirm = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public LogbookForm $form;

    public ?string $supervisorNoteEntryId = null;

    public string $supervisorNote = '';

    public function boot(): void
    {
        $this->authorize('viewAny', Logbook::class);
    }

    public function headers(): array
    {
        $headers = [
            ['key' => 'user.name', 'label' => __('logbook.student'), 'sortable' => true],
            ['key' => 'date', 'label' => __('logbook.date'), 'sortable' => true],
            ['key' => 'content', 'label' => __('logbook.content')],
            ['key' => 'status', 'label' => __('logbook.status'), 'sortable' => true],
            ['key' => 'is_verified', 'label' => __('logbook.verified')],
        ];

        if (auth()->user()?->hasRole('supervisor')) {
            $headers[] = ['key' => 'supervisor_note', 'label' => __('logbook.supervisor_note')];
        }

        $headers[] = ['key' => 'actions', 'label' => '', 'sortable' => false];

        return $headers;
    }

    protected function query(): Builder
    {
        $user = auth()->user();

        $query = Logbook::query()->with(['user', 'registration', 'verifier', 'supervisor']);

        if ($user->hasRole('teacher')) {
            $query->whereHas(
                'registration',
                fn ($q) => $q->whereHas(
                    'mentors',
                    fn ($mq) => $mq
                        ->where('user_id', $user->id)
                        ->where('internship_group_members.role', 'teacher'),
                ),
            );
        } elseif ($user->hasRole('supervisor')) {
            $query->whereHas(
                'registration',
                fn ($q) => $q->whereHas(
                    'mentors',
                    fn ($mq) => $mq
                        ->where('user_id', $user->id)
                        ->where('internship_group_members.role', 'supervisor'),
                ),
            );
        }

        return $query;
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('content', 'like', "%{$this->search}%")->orWhereHas(
                'user',
                fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"),
            );
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(
                $this->filters['is_verified'] ?? null,
                fn ($q, $v) => $q->where('is_verified', $v === 'yes'),
            );
    }

    #[Computed]
    public function students(): array
    {
        $query = User::role('student');

        $user = auth()->user();
        if ($user->hasRole('teacher')) {
            $query->whereHas(
                'registrations',
                fn ($q) => $q->whereHas(
                    'mentors',
                    fn ($mq) => $mq
                        ->where('user_id', $user->id)
                        ->where('internship_group_members.role', 'teacher'),
                ),
            );
        } elseif ($user->hasRole('supervisor')) {
            $query->whereHas(
                'registrations',
                fn ($q) => $q->whereHas(
                    'mentors',
                    fn ($mq) => $mq
                        ->where('user_id', $user->id)
                        ->where('internship_group_members.role', 'supervisor'),
                ),
            );
        }

        return $query
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => "{$s->name} ({$s->email})"])
            ->toArray();
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->form->date = now()->toDateString();
        $this->showModal = true;
    }

    public function edit(Logbook $entry): void
    {
        $this->resetErrorBag();
        $this->form->id = $entry->id;
        $this->form->userId = $entry->user_id;
        $this->form->date = $entry->date->format('Y-m-d');
        $this->form->content = $entry->content;
        $this->form->learningOutcomes = $entry->learning_outcomes ?? '';
        $this->form->status = $entry->status->value;
        $this->form->mentorFeedback = $entry->mentor_feedback ?? '';
        $this->showModal = true;
    }

    public function save(CreateLogbookAction $create, UpdateLogbookAction $update): void
    {
        $this->validate([
            'form.date' => 'required|date',
            'form.content' => 'required|string|min:10',
            'form.status' => 'required|string',
        ]);

        if ($this->form->id) {
            $entry = Logbook::findOrFail($this->form->id);
            $update->execute($entry, $this->form->toArray());
            flash()->success(__('logbook.success_updated'));
        } else {
            $this->validate(['form.userId' => 'required|exists:users,id']);

            $create->execute($this->form->userId, $this->form->toArray());
            flash()->success(__('logbook.success_created'));
        }

        $this->showModal = false;
    }

    public function askDelete(string $id): void
    {
        $this->confirmActionType = 'delete';
        $this->confirmTarget = $id;
        $this->showConfirm = true;
    }

    public function askDeleteSelected(): void
    {
        $this->confirmActionType = 'deleteSelected';
        $this->showConfirm = true;
    }

    public function confirmAction(DeleteLogbookAction $deleteAction): void
    {
        try {
            if ($this->confirmActionType === 'delete') {
                $deleteAction->execute(Logbook::findOrFail($this->confirmTarget));
                flash()->success(__('logbook.success_deleted'));
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
                    $entry = Logbook::find($id);
                    if ($entry) {
                        $deleteAction->execute($entry);
                    }
                });
            }
        } catch (RejectedException $e) {
            flash()->error($e->getMessage());
        }

        $this->showConfirm = false;
        $this->confirmTarget = null;
        $this->confirmActionType = '';
    }

    // --- Verification ---

    public function verify(Logbook $entry, UpdateLogbookAction $update): void
    {
        $update->execute($entry, [
            'is_verified' => ! $entry->is_verified,
        ]);
        flash()->success(__('logbook.success_verified'));
    }

    // --- Supervisor Note ---

    public function editSupervisorNote(Logbook $entry): void
    {
        $this->resetErrorBag();
        $this->supervisorNoteEntryId = $entry->id;
        $this->supervisorNote = $entry->supervisor_note ?? '';
        $this->showSupervisorNoteModal = true;
    }

    public function saveSupervisorNote(UpdateLogbookAction $update): void
    {
        $this->validate([
            'supervisorNote' => ['nullable', 'string', 'max:5000'],
        ]);

        $entry = Logbook::findOrFail($this->supervisorNoteEntryId);

        $this->authorize('addSupervisorNote', $entry);

        $update->execute($entry, [
            'supervisor_note' => $this->supervisorNote,
            'supervisor_id' => auth()->id(),
            'supervisor_reviewed_at' => now()->toDateTimeString(),
        ]);

        $this->showSupervisorNoteModal = false;
        $this->supervisorNoteEntryId = null;
        $this->supervisorNote = '';

        flash()->success(__('logbook.supervisor_note_saved'));
    }

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        return view('journals.logbook.logbook-manager');
    }
}
