<?php

declare(strict_types=1);

namespace App\Livewire\Logbook;

use App\Actions\Logbook\CreateLogbookAction;
use App\Actions\Logbook\DeleteLogbookAction;
use App\Actions\Logbook\UpdateLogbookAction;
use App\Livewire\Core\BaseRecordManager;
use App\Models\Logbook;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

class LogbookManager extends BaseRecordManager
{
    public bool $showModal = false;

    public array $formData = [
        'id' => null,
        'user_id' => '',
        'date' => '',
        'content' => '',
        'learning_outcomes' => '',
        'status' => 'draft',
        'mentor_feedback' => '',
    ];

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
        return [
            ['key' => 'user.name', 'label' => __('logbook.student'), 'sortable' => true],
            ['key' => 'date', 'label' => __('logbook.date'), 'sortable' => true],
            ['key' => 'content', 'label' => __('logbook.content')],
            ['key' => 'status', 'label' => __('logbook.status'), 'sortable' => true],
            ['key' => 'is_verified', 'label' => __('logbook.verified')],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    protected function query(): Builder
    {
        $user = auth()->user();

        $query = Logbook::query()
            ->with(['user', 'registration', 'verifier']);

        if ($user->hasRole('teacher')) {
            $query->whereHas('registration', fn ($q) => $q->where('teacher_id', $user->id));
        } elseif ($user->hasRole('supervisor')) {
            $query->whereHas('registration', fn ($q) => $q->where('mentor_id', $user->id));
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

    #[Computed]
    public function students(): array
    {
        $query = User::role('student');

        $user = auth()->user();
        if ($user->hasRole('teacher')) {
            $query->whereHas('registrations', fn ($q) => $q->where('teacher_id', $user->id));
        } elseif ($user->hasRole('supervisor')) {
            $query->whereHas('registrations', fn ($q) => $q->where('mentor_id', $user->id));
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

    public function render()
    {
        return view('livewire.logbook.logbook-manager');
    }
}
