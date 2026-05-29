<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Livewire\Concerns\DownloadsAccountSlips;
use App\Domain\Admin\Livewire\Forms\MenteeForm;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Mentee\Actions\CreateMenteeAction;
use App\Domain\Mentee\Actions\DeleteMenteeAction;
use App\Domain\Mentee\Actions\UpdateMenteeAction;
use App\Domain\Mentee\Models\Mentee;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class MenteeManager extends BaseRecordManager
{
    use AuthorizesRequests, DownloadsAccountSlips;

    public bool $userModal = false;

    public MenteeForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', Mentee::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.mentee.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('user.mentee.active')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return Mentee::query()
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
            ->when($this->filters['is_active'] ?? null, fn ($q, $v) => $q->where('is_active', $v === 'yes'))
            ->when($this->filters['created_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($this->filters['created_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v));
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->userModal = true;
    }

    public function edit(string $id): void
    {
        $mentee = Mentee::with('user')->findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $mentee->id,
            'name' => $mentee->user->name,
            'email' => $mentee->user->email,
            'internal_notes' => $mentee->internal_notes ?? '',
            'is_active' => $mentee->is_active,
            'editingUserId' => $mentee->user_id,
        ]);
        $this->userModal = true;
    }

    public function save(CreateMenteeAction $createAction, UpdateMenteeAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $mentee = Mentee::with('user')->findOrFail($this->form->id);
            $updateAction->execute($mentee, [
                'internal_notes' => $this->form->internal_notes,
                'is_active' => $this->form->is_active,
            ]);
            flash()->success(__('user.mentee.success_updated'));
        } else {
            $mentee = $createAction->execute(
                userData: [
                    'name' => $this->form->name,
                    'email' => $this->form->email,
                ],
                menteeData: [
                    'internal_notes' => $this->form->internal_notes,
                    'is_active' => $this->form->is_active,
                ],
            );
            $this->userModal = false;
            $this->redirect(route('admin.users.account-slip', $mentee->user));

            return;
        }

        $this->userModal = false;
    }

    public function delete(string $id, DeleteMenteeAction $deleteAction): void
    {
        $mentee = Mentee::findOrFail($id);

        $deleteAction->execute($mentee);
        flash()->success(__('user.mentee.success_deleted'));
    }

    public function deleteSelected(DeleteMenteeAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $mentee = Mentee::find($id);
            if ($mentee) {
                $deleteAction->execute($mentee);
            }
        });
    }

    public function render(): View
    {
        return view('admin.mentee-manager');
    }
}
