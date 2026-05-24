<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Actions\CreateUserAction;
use App\Domain\Admin\Actions\DeleteUserAction;
use App\Domain\Admin\Actions\ToggleUserStatusAction;
use App\Domain\Admin\Actions\UpdateUserAction;
use App\Domain\Admin\Livewire\Forms\UserForm;
use App\Domain\Auth\Actions\ResetUserPasswordAction;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;

class UserManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $userModal = false;

    public UserForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

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
        $this->form->reset();
        $this->userModal = true;
    }

    public function editUser(string $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
        ]);
        $this->userModal = true;
    }

    public function saveUser(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $user = User::findOrFail($this->form->id);
            $updateAction->execute($user, ['name' => $this->form->name, 'email' => $this->form->email], null, $this->form->roles);
            flash()->success(__('user.manager.success_updated'));
        } else {
            $createAction->execute(['name' => $this->form->name, 'email' => $this->form->email], [], $this->form->roles);
            flash()->success(__('user.manager.success_created'));
        }

        $this->userModal = false;
    }

    public function toggleStatus(string $id, ToggleUserStatusAction $action): void
    {
        $user = User::findOrFail($id);

        try {
            $action->execute($user);
            flash()->success(__('user.manager.status_changed'));
        } catch (\RuntimeException $e) {
            flash()->error($e->getMessage());
        }
    }

    public function resetPassword(string $id, ResetUserPasswordAction $action): void
    {
        $user = User::findOrFail($id);

        $result = $action->execute($user);
        flash()->info(__('user.manager.password_reset', ['password' => $result['new_password']]));
    }

    public function deleteUser(string $id, DeleteUserAction $deleteAction): void
    {
        $user = User::findOrFail($id);

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

    public function render(): View
    {
        return view('admin.manager');
    }
}
