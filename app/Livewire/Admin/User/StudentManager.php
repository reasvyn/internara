<?php

declare(strict_types=1);

namespace App\Livewire\Admin\User;

use App\Actions\Auth\CreateUserAction;
use App\Actions\Auth\DeleteUserAction;
use App\Actions\Auth\UpdateUserAction;
use App\Enums\Role as RoleEnum;
use App\Livewire\BaseRecordManager;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Computed;

/**
 * Modernized Student Manager using BaseRecordManager pattern.
 *
 * S2 - Sustain: Clean, reusable, and follows architecture guidelines.
 */
class StudentManager extends BaseRecordManager
{
    public bool $userModal = false;

    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'national_identifier' => '',
        'registration_number' => '',
        'department_id' => '',
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
            ['key' => 'name', 'label' => __('user.student.name'), 'sortable' => true],
            ['key' => 'username', 'label' => __('user.student.username'), 'class' => 'font-mono text-xs'],
            ['key' => 'profile.national_identifier', 'label' => __('user.student.nisn')],
            ['key' => 'profile.registration_number', 'label' => __('user.student.nis')],
            ['key' => 'profile.department.name', 'label' => __('user.student.department')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => ''],
        ];
    }

    /**
     * Base query for students.
     */
    protected function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::STUDENT->value)
            ->with(['profile.department']);
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
        return $query->when($this->filters['department_id'] ?? null, function ($q, $deptId) {
            $q->whereHas('profile', fn ($qp) => $qp->where('department_id', $deptId));
        });
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get();
    }

    // --- Record Actions ---

    public function createUser(): void
    {
        $this->resetErrorBag();
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
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
            'userData.email' => 'required|email|unique:users,email,'.($this->userData['id'] ?? 'NULL'),
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
            $this->success(__('user.student.success_updated'));
        } else {
            $createAction->execute($this->userData, $profileData, [RoleEnum::STUDENT->value]);
            $this->success(__('user.student.success_created'));
        }

        $this->userModal = false;
    }

    public function deleteUser(User $user, DeleteUserAction $deleteAction): void
    {
        $deleteAction->execute($user);
        $this->success(__('user.student.success_deleted'));
    }

    // --- Bulk Actions (Selected Rows) ---

    public function deleteSelected(DeleteUserAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $user = User::find($id);
            if ($user) {
                $deleteAction->execute($user);
            }
        });
    }

    // --- Mass Actions (Active Query) ---

    public function archiveAllFiltered(): void
    {
        $this->performMassAction('Archive Filtered', function ($query) {
            $query->each(fn ($user) => $user->setStatus('archived', 'Mass archived via Student Manager'));
        });
    }

    public function render()
    {
        return view('livewire.admin.user.student-manager');
    }
}
