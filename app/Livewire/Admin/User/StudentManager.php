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

class StudentManager extends Component
{
    use WithPagination, Toast;

    public string $search = '';
    
    public bool $userModal = false;
    
    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'username' => '',
        'national_identifier' => '',
        'registration_number' => '',
    ];

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'username', 'label' => 'Username'],
            ['key' => 'profile.national_identifier', 'label' => 'NISN'],
            ['key' => 'profile.registration_number', 'label' => 'NIS'],
            ['key' => 'created_at', 'label' => 'Joined', 'sortable' => true],
        ];
    }

    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->role(RoleEnum::STUDENT->value)
            ->with(['profile'])
            ->when($this->search, function (Builder $q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
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
            'national_identifier' => '',
            'registration_number' => '',
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
            'national_identifier' => $user->profile?->national_identifier ?? '',
            'registration_number' => $user->profile?->registration_number ?? '',
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

        $profileData = [
            'national_identifier' => $this->userData['national_identifier'],
            'registration_number' => $this->userData['registration_number'],
        ];

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $updateAction->execute($user, $this->userData, $profileData);
            $this->success('Student updated.');
        } else {
            $createAction->execute($this->userData, $profileData, [RoleEnum::STUDENT->value]);
            $this->success('Student created.');
        }

        $this->userModal = false;
    }

    public function deleteUser(User $user, DeleteUserAction $deleteAction): void
    {
        $deleteAction->execute($user);
        $this->success('Student deleted.');
    }

    public function render()
    {
        return view('livewire.admin.user.student-manager', [
            'users' => $this->users(),
            'headers' => $this->headers(),
        ]);
    }
}
