<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Auth\CreateUserAction;
use App\Actions\Auth\DeleteUserAction;
use App\Actions\Auth\UpdateUserAction;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Role;

class UserManager extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    
    public array $filters = [
        'role' => null,
    ];

    public bool $userModal = false;
    
    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'username' => '',
        'roles' => [],
    ];

    /**
     * Get the headers for the table.
     */
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'roles', 'label' => 'Roles'],
            ['key' => 'created_at', 'label' => 'Joined', 'sortable' => true],
        ];
    }

    /**
     * Get the users for the table.
     */
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles'])
            ->when($this->search, function (Builder $q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
            })
            ->when($this->filters['role'], function (Builder $q) {
                $q->role($this->filters['role']);
            })
            ->latest()
            ->paginate(10);
    }

    /**
     * Open modal for creating a new user.
     */
    public function createUser(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'username' => '',
            'roles' => [],
        ];
        $this->userModal = true;
    }

    /**
     * Open modal for editing a user.
     */
    public function editUser(User $user): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'roles' => $user->roles->pluck('name')->toArray(),
        ];
        $this->userModal = true;
    }

    /**
     * Save the user (create or update).
     */
    public function saveUser(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $rules = [
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,' . ($this->userData['id'] ?? 'NULL'),
            'userData.username' => 'required|string|unique:users,username,' . ($this->userData['id'] ?? 'NULL'),
            'userData.roles' => 'required|array|min:1',
        ];

        $this->validate($rules);

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $updateAction->execute($user, $this->userData, null, $this->userData['roles']);
            $this->success('User updated successfully.');
        } else {
            $createAction->execute($this->userData, [], $this->userData['roles']);
            $this->success('User created successfully.');
        }

        $this->userModal = false;
    }

    /**
     * Delete a user.
     */
    public function deleteUser(User $user, DeleteUserAction $deleteAction): void
    {
        if ($user->id === auth()->id()) {
            $this->error('You cannot delete yourself.');
            return;
        }

        $deleteAction->execute($user);
        $this->success('User deleted successfully.');
    }

    public function render()
    {
        return view('livewire.admin.user-manager', [
            'users' => $this->users(),
            'roles' => Role::all(),
            'headers' => $this->headers(),
        ]);
    }
}
