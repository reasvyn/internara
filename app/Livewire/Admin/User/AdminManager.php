<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

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

class AdminManager extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    
    public bool $userModal = false;
    
    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'username' => '',
        'roles' => [RoleEnum::ADMIN->value],
    ];

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'created_at', 'label' => 'Joined', 'sortable' => true],
        ];
    }

    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->role([RoleEnum::ADMIN->value, RoleEnum::SUPER_ADMIN->value])
            ->when($this->search, function (Builder $q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
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
            'roles' => [RoleEnum::ADMIN->value],
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
        $this->validate([
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,' . ($this->userData['id'] ?? 'NULL'),
            'userData.username' => 'required|string|unique:users,username,' . ($this->userData['id'] ?? 'NULL'),
        ]);

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $updateAction->execute($user, $this->userData);
            $this->success('Admin updated.');
        } else {
            $createAction->execute($this->userData, [], $this->userData['roles']);
            $this->success('Admin created.');
        }

        $this->userModal = false;
    }

    public function deleteUser(User $user, DeleteUserAction $deleteAction): void
    {
        if ($user->id === auth()->id()) {
            $this->error('Cannot delete yourself.');
            return;
        }

        $deleteAction->execute($user);
        $this->success('Admin deleted.');
    }

    public function render()
    {
        return view('livewire.admin.user.admin-manager', [
            'users' => $this->users(),
            'headers' => $this->headers(),
        ]);
    }
}
