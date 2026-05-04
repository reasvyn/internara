<?php

declare(strict_types=1);

namespace App\Livewire\User\Admin;

use App\Domain\Auth\Enums\AccountStatus;
use App\Domain\User\Actions\CreateUserAction;
use App\Domain\User\Actions\DeleteUserAction;
use App\Domain\User\Actions\UpdateUserAction;
use App\Domain\User\Models\User;
use App\Domain\User\Notifications\AccountStatusNotification;
use App\Livewire\Core\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Computed;
use Spatie\Permission\Models\Role;

/**
 * Modernized User Manager using BaseRecordManager pattern.
 */
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

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'email', 'label' => 'Account Info'],
            ['key' => 'roles_list', 'label' => 'Roles'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for users.
     */
    protected function query(): Builder
    {
        return User::query()->with(['roles', 'statuses']);
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

    /**
     * Filter implementation.
     */
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

    // --- Record Actions ---

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
        $newStatus =
            $currentStatus === AccountStatus::VERIFIED->value
                ? AccountStatus::SUSPENDED->value
                : AccountStatus::VERIFIED->value;

        $user->setStatus($newStatus, 'Changed via User Manager');

        // Notify User
        $user->notify(new AccountStatusNotification($newStatus, 'Updated by Administrator'));

        $this->success("User status changed to {$newStatus}. Notification sent.");
    }

    public function resetPassword(User $user): void
    {
        $newPassword = str()->random(10);
        $user->update(['password' => Hash::make($newPassword)]);

        $this->info(
            "Password reset to: {$newPassword}",
            'Temp Password',
            position: 'toast-bottom-center',
            timeout: 10000,
        );
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

    // --- Bulk Actions ---

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
