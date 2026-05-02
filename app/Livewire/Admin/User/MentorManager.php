<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Actions\Auth\CreateUserAction;
use App\Actions\Auth\DeleteUserAction;
use App\Actions\Auth\UpdateUserAction;
use App\Enums\Role as RoleEnum;
use App\Livewire\BaseRecordManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Modernized Mentor Manager using BaseRecordManager pattern.
 */
class MentorManager extends BaseRecordManager
{
    public bool $userModal = false;

    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'phone' => '',
    ];

    public function boot(): void
    {
        if (! auth()->user()?->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => __('user.mentor.name'), 'sortable' => true],
            ['key' => 'username', 'label' => __('user.fields.username'), 'class' => 'font-mono text-xs'],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            ['key' => 'profile.phone', 'label' => __('user.mentor.phone')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for mentors.
     */
    protected function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::MENTOR->value)
            ->with(['profile']);
    }

    /**
     * Search implementation.
     */
    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('username', 'like', "%{$this->search}%");
        });
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'phone' => '',
        ];
        $this->userModal = true;
    }

    public function edit(User $user): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->profile?->phone ?? '',
        ];
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->validate([
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,'.($this->userData['id'] ?? 'NULL'),
        ]);

        $profileData = [
            'phone' => $this->userData['phone'],
        ];

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $updateAction->execute($user, $this->userData, $profileData);
            $this->success(__('user.mentor.success_updated', default: 'Mentor updated.'));
        } else {
            $createAction->execute($this->userData, $profileData, [RoleEnum::MENTOR->value]);
            $this->success(__('user.mentor.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(User $user, DeleteUserAction $deleteAction): void
    {
        $deleteAction->execute($user);
        $this->success(__('user.mentor.success_deleted', default: 'Mentor deleted.'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeleteUserAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $user = User::find($id);
            if ($user) {
                $deleteAction->execute($user);
            }
        });
    }

    public function render()
    {
        return view('livewire.admin.user.mentor-manager');
    }
}
