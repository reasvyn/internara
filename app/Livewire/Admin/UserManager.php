<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Auth\CreateUserAction;
use App\Actions\Auth\DeleteUserAction;
use App\Actions\Auth\UpdateUserAction;
use App\Enums\AccountStatus;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
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
        'status' => null,
    ];

    public bool $userModal = false;
    
    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'username' => '',
        'roles' => [],
        'password' => '',
    ];

    /**
     * Get the headers for the table.
     */
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'email', 'label' => 'Account Info'],
            ['key' => 'roles', 'label' => 'Roles', 'sortable' => false],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => '', 'sortable' => false]
        ];
    }

    /**
     * Get the users for the table.
     */
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->with(['roles', 'statuses'])
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

    public function createUser(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'username' => '',
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
            'username' => $user->username,
            'roles' => $user->roles->pluck('name')->toArray(),
        ];
        $this->userModal = true;
    }

    public function saveUser(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $rules = [
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,' . ($this->userData['id'] ?? 'NULL'),
            'userData.username' => 'required|string|unique:users,username,' . ($this->userData['id'] ?? 'NULL'),
            'userData.roles' => 'required|array|min:1',
        ];

        if (!$this->userData['id']) {
            $rules['userData.password'] = 'required|min:8';
        }

        $this->validate($rules);

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $updateAction->execute($user, $this->userData, null, $this->userData['roles']);
            $this->success('User updated.');
        } else {
            $createAction->execute($this->userData, [], $this->userData['roles']);
            $this->success('User created.');
        }

        $this->userModal = false;
    }

    public function toggleStatus(User $user): void
    {
        if ($user->id === auth()->id()) {
            $this->error('Cannot change your own status.');
            return;
        }

        $currentStatus = $user->latestStatus()?->name;
        $newStatus = $currentStatus === AccountStatus::ACTIVE->value 
            ? AccountStatus::SUSPENDED->value 
            : AccountStatus::ACTIVE->value;

        $user->setStatus($newStatus, 'Changed via User Manager');
        $this->success("User status changed to {$newStatus}.");
    }

    public function resetPassword(User $user): void
    {
        $newPassword = str()->random(10);
        $user->update(['password' => Hash::make($newPassword)]);
        
        $this->info("Password reset to: {$newPassword}", 'Temp Password', position: 'toast-bottom-center', timeout: 10000);
    }

    public function deleteUser(User $user, DeleteUserAction $deleteAction): void
    {
        if ($user->id === auth()->id()) {
            $this->error('You cannot delete yourself.');
            return;
        }

        $deleteAction->execute($user);
        $this->success('User deleted.');
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
