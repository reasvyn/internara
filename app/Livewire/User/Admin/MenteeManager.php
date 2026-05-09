<?php

declare(strict_types=1);

namespace App\Livewire\User\Admin;

use App\Actions\Mentee\CreateMenteeAction;
use App\Actions\Mentee\DeleteMenteeAction;
use App\Actions\Mentee\UpdateMenteeAction;
use App\Livewire\Core\BaseRecordManager;
use App\Models\Mentee;
use Illuminate\Database\Eloquent\Builder;

class MenteeManager extends BaseRecordManager
{
    public bool $userModal = false;

    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'internal_notes' => '',
        'is_active' => true,
    ];

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403, 'Unauthorized access.');
        }
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => __('user.mentee.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            ['key' => 'is_active', 'label' => __('user.mentee.active')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
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

    public function create(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'internal_notes' => '',
            'is_active' => true,
        ];
        $this->userModal = true;
    }

    public function edit(Mentee $mentee): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => $mentee->id,
            'name' => $mentee->user->name,
            'email' => $mentee->user->email,
            'internal_notes' => $mentee->internal_notes ?? '',
            'is_active' => $mentee->is_active,
        ];
        $this->userModal = true;
    }

    public function save(CreateMenteeAction $createAction, UpdateMenteeAction $updateAction): void
    {
        $this->validate([
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,'.($this->userData['id'] ? Mentee::find($this->userData['id'])?->user_id ?? 'NULL' : 'NULL'),
        ]);

        if ($this->userData['id']) {
            $mentee = Mentee::with('user')->findOrFail($this->userData['id']);
            $updateAction->execute($mentee, [
                'internal_notes' => $this->userData['internal_notes'],
                'is_active' => $this->userData['is_active'],
            ]);
            $this->success(__('user.mentee.success_updated'));
        } else {
            $createAction->execute(
                userData: [
                    'name' => $this->userData['name'],
                    'email' => $this->userData['email'],
                ],
                menteeData: [
                    'internal_notes' => $this->userData['internal_notes'],
                    'is_active' => $this->userData['is_active'],
                ],
            );
            $this->success(__('user.mentee.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(Mentee $mentee, DeleteMenteeAction $deleteAction): void
    {
        $deleteAction->execute($mentee);
        $this->success(__('user.mentee.success_deleted'));
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

    public function render()
    {
        return view('livewire.user.mentee-manager');
    }
}
