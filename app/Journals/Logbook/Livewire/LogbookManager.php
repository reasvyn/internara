<?php

declare(strict_types=1);

namespace App\Journals\Logbook\Livewire;

use App\Core\Livewire\BaseRecordManager;
use App\Guidance\Mentor\Models\Mentor;
use App\Journals\Logbook\Actions\CreateLogbookAction;
use App\Journals\Logbook\Actions\DeleteLogbookAction;
use App\Journals\Logbook\Actions\UpdateLogbookAction;
use App\Journals\Logbook\Models\Logbook;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;

class LogbookManager extends BaseRecordManager
{
    public bool $showModal = false;

    public bool $showSupervisorNoteModal = false;

    public array $formData = [
        'id' => null,
        'user_id' => '',
        'date' => '',
        'content' => '',
        'learning_outcomes' => '',
        'status' => 'draft',
        'mentor_feedback' => '',
    ];

    public ?string $supervisorNoteEntryId = null;

    public string $supervisorNote = '';

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin', 'teacher', 'supervisor'])
        ) {
            abort(403, 'Unauthorized access.');
        }
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

        $query = Logbook::query()
            ->with(['user', 'registration', 'verifier', 'supervisor']);

        if ($user->hasRole('teacher')) {
            $query->whereHas('registration', fn ($q) => $q->whereHas('mentors', fn ($mq) => $mq->where('user_id', $user->id)->where('type', Mentor::TYPE_SCHOOL_TEACHER)));
        } elseif ($user->hasRole('supervisor')) {
            $query->whereHas('registration', fn ($q) => $q->whereHas('mentors', fn ($mq) => $mq->where('user_id', $user->id)->where('type', Mentor::TYPE_INDUSTRY_SUPERVISOR)));
        }

        return $query;
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('content', 'like', "%{$this->search}%")
                ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($this->filters['is_verified'] ?? null, fn ($q, $v) => $q->where('is_verified', $v === 'yes'));
    }

    #[Computed]
    public function students(): array
    {
        $query = User::role('student');

        $user = auth()->user();
        if ($user->hasRole('teacher')) {
            $query->whereHas('registrations', fn ($q) => $q->whereHas('mentors', fn ($mq) => $mq->where('user_id', $user->id)->where('type', Mentor::TYPE_SCHOOL_TEACHER)));
        } elseif ($user->hasRole('supervisor')) {
            $query->whereHas('registrations', fn ($q) => $q->whereHas('mentors', fn ($mq) => $mq->where('user_id', $user->id)->where('type', Mentor::TYPE_INDUSTRY_SUPERVISOR)));
        }

        return $query->orderBy('name')
            ->get(['id', 'name', 'email'])
            ->map(fn ($s) => ['id' => $s->id, 'name' => "{$s->name} ({$s->email})"])
            ->toArray();
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => null,
            'user_id' => '',
            'date' => now()->toDateString(),
            'content' => '',
            'learning_outcomes' => '',
            'status' => 'draft',
            'mentor_feedback' => '',
        ];
        $this->showModal = true;
    }

    public function edit(Logbook $entry): void
    {
        $this->resetErrorBag();
        $this->formData = [
            'id' => $entry->id,
            'user_id' => $entry->user_id,
            'date' => $entry->date->format('Y-m-d'),
            'content' => $entry->content,
            'learning_outcomes' => $entry->learning_outcomes ?? '',
            'status' => $entry->status->value,
            'mentor_feedback' => $entry->mentor_feedback ?? '',
        ];
        $this->showModal = true;
    }

    public function save(CreateLogbookAction $create, UpdateLogbookAction $update): void
    {
        $this->validate([
            'formData.date' => 'required|date',
            'formData.content' => 'required|string|min:10',
            'formData.status' => 'required|string',
        ]);

        if ($this->formData['id']) {
            $entry = Logbook::findOrFail($this->formData['id']);
            $update->execute($entry, $this->formData);
            flash()->success(__('logbook.success_updated'));
        } else {
            $this->validate(['formData.user_id' => 'required|exists:users,id']);

            $create->execute($this->formData['user_id'], $this->formData);
            flash()->success(__('logbook.success_created'));
        }

        $this->showModal = false;
    }

    public function delete(Logbook $entry, DeleteLogbookAction $deleteAction): void
    {
        $deleteAction->execute($entry);
        flash()->success(__('logbook.success_deleted'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeleteLogbookAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $entry = Logbook::find($id);
            if ($entry) {
                $deleteAction->execute($entry);
            }
        });
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
