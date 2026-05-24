<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Livewire\Forms\MentorForm;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Mentor\Actions\CreateMentorAction;
use App\Domain\Mentor\Actions\DeleteMentorAction;
use App\Domain\Mentor\Actions\UpdateMentorAction;
use App\Domain\Mentor\Models\Mentor;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MentorManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $userModal = false;

    public MentorForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Mentor::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => __('user.mentor.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            ['key' => 'type', 'label' => __('user.mentor.type'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('user.mentor.active')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Mentor::query()
            ->with('user');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('user', fn ($uq) => $uq->where('email', 'like', "%{$this->search}%"));
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['type'] ?? null, fn ($q, $v) => $q->where('type', $v))
            ->when($this->filters['is_active'] ?? null, fn ($q, $v) => $q->where('is_active', $v === 'yes'));
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->userModal = true;
    }

    public function edit(string $id): void
    {
        $mentor = Mentor::with('user')->findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $mentor->id,
            'name' => $mentor->user->name,
            'email' => $mentor->user->email,
            'type' => $mentor->type,
            'is_active' => $mentor->is_active,
            'editingUserId' => $mentor->user_id,
        ]);
        $this->userModal = true;
    }

    public function save(CreateMentorAction $createAction, UpdateMentorAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $mentor = Mentor::with('user')->findOrFail($this->form->id);
            $updateAction->execute($mentor, [
                'type' => $this->form->type,
                'is_active' => $this->form->is_active,
            ]);
            flash()->success(__('user.mentor.success_updated'));
        } else {
            $createAction->execute(
                userData: [
                    'name' => $this->form->name,
                    'email' => $this->form->email,
                ],
                mentorData: [
                    'type' => $this->form->type,
                    'is_active' => $this->form->is_active,
                ],
            );
            flash()->success(__('user.mentor.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(string $id, DeleteMentorAction $deleteAction): void
    {
        $mentor = Mentor::findOrFail($id);

        $deleteAction->execute($mentor);
        flash()->success(__('user.mentor.success_deleted'));
    }

    public function deleteSelected(DeleteMentorAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $mentor = Mentor::find($id);
            if ($mentor) {
                $deleteAction->execute($mentor);
            }
        });
    }

    public function render(): View
    {
        return view('admin.mentor-manager');
    }
}
