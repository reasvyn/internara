<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Actions\Auth\CreateUserAction;
use App\Actions\Auth\DeleteUserAction;
use App\Actions\Auth\UpdateUserAction;
use App\Enums\Role as RoleEnum;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

class StudentManager extends Component
{
    use WithPagination, Toast;

    public function boot(): void
    {
        if (!auth()->user()?->hasAnyRole(['super_admin', 'admin'])) {
            abort(403, 'Unauthorized access.');
        }
    }

    public string $search = '';
    
    public array $filters = [
        'department_id' => null,
    ];

    public bool $userModal = false;
    
    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'username' => '',
        'national_identifier' => '',
        'registration_number' => '',
        'department_id' => '',
    ];

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get();
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Student Name', 'sortable' => true],
            ['key' => 'profile.national_identifier', 'label' => 'NISN'],
            ['key' => 'profile.registration_number', 'label' => 'NIS'],
            ['key' => 'profile.department.name', 'label' => 'Department'],
            ['key' => 'created_at', 'label' => 'Joined', 'sortable' => true],
            ['key' => 'actions', 'label' => '']
        ];
    }

    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->role(RoleEnum::STUDENT->value)
            ->with(['profile.department'])
            ->when($this->search, function (Builder $q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%");
            })
            ->when($this->filters['department_id'], function (Builder $q) {
                $q->whereHas('profile', fn($qp) => $qp->where('department_id', $this->filters['department_id']));
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
            'department_id' => '',
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
            'department_id' => $user->profile?->department_id ?? '',
        ];
        $this->userModal = true;
    }

    public function saveUser(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->validate([
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,' . ($this->userData['id'] ?? 'NULL'),
            'userData.username' => 'required|string|unique:users,username,' . ($this->userData['id'] ?? 'NULL'),
            'userData.national_identifier' => 'required|string|max:20',
            'userData.department_id' => 'required|exists:departments,id',
        ]);

        $profileData = [
            'national_identifier' => $this->userData['national_identifier'],
            'registration_number' => $this->userData['registration_number'],
            'department_id' => $this->userData['department_id'],
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
