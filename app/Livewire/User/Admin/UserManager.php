<?php

declare(strict_types=1);

namespace App\Livewire\User\Admin;

use App\Actions\User\CreateUserAction;
use App\Actions\User\DeleteUserAction;
use App\Actions\User\ResetUserPasswordAction;
use App\Actions\User\ToggleUserStatusAction;
use App\Actions\User\UpdateUserAction;
use App\Livewire\Core\BaseRecordManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;

class UserManager extends BaseRecordManager
{
    public bool $userModal = false;

    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'roles' => [],
        'password' => '',
    ];

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.manager.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.manager.email')],
            ['key' => 'roles_list', 'label' => __('user.manager.roles')],
            ['key' => 'status', 'label' => __('user.manager.status')],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()->with(['roles', 'statuses']);
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orWhere('username', 'like', "%{$this->search}%");
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['role'] ?? null, function ($q, $role) {
                $q->role($role);
            })
            ->when($this->filters['status'] ?? null, function ($q, $status) {
                $q->whereHas('statuses', fn ($qs) => $qs->where('name', $status)->latest());
            });
    }

    #[Computed]
    public function roles()
    {
        return Role::all();
    }

    public function createUser(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'roles' => [],
            'password' => '',
        ];
        $this->userModal = true;
    }

    public function editUser(User $user): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
        ];
        $this->userModal = true;
    }

    public function saveUser(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $rules = [
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,'.($this->userData['id'] ?? 'NULL'),
            'userData.roles' => 'required|array|min:1',
        ];

        if (! $this->userData['id']) {
            $rules['userData.password'] = 'required|min:8';
        }

        $this->validate($rules);

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $updateAction->execute($user, $this->userData, null, $this->userData['roles']);
            flash()->success(__('user.manager.success_updated'));
        } else {
            $createAction->execute($this->userData, [], $this->userData['roles']);
            flash()->success(__('user.manager.success_created'));
        }

        $this->userModal = false;
    }

    public function toggleStatus(User $user, ToggleUserStatusAction $action): void
    {
        try {
            $action->execute($user);
            flash()->success(__('user.manager.status_changed'));
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function resetPassword(User $user, ResetUserPasswordAction $action): void
    {
        $result = $action->execute($user);
        flash()->info(__('user.manager.password_reset', ['password' => $result['new_password']]));
    }

    public function deleteUser(User $user, DeleteUserAction $deleteAction): void
    {
        try {
            $deleteAction->execute($user);
            flash()->success(__('user.manager.success_deleted'));
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function deleteSelected(DeleteUserAction $deleteAction): void
    {
        $this->performBulkAction('Delete', function ($id) use ($deleteAction) {
            if ($id === auth()->id()) {
                return;
            }
            $user = User::find($id);
            if ($user) {
                $deleteAction->execute($user);
            }
        });
    }

    public function render()
    {
        return view('livewire.user.manager');
    }
}
