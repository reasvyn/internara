<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Actions\ArchiveStudentAccountsAction;
use App\Domain\Admin\Actions\CreateUserAction;
use App\Domain\Admin\Actions\DeleteUserAction;
use App\Domain\Admin\Actions\UpdateUserAction;
use App\Domain\Admin\Livewire\Forms\StudentForm;
use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\School\Models\Department;
use App\Domain\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;

class StudentManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $userModal = false;

    public StudentForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.student.name'), 'sortable' => true],
            [
                'key' => 'username',
                'label' => __('user.student.username'),
                'class' => 'font-mono text-xs',
            ],
            ['key' => 'profile.national_id_number', 'label' => __('user.student.nisn')],
            ['key' => 'profile.student_id_number', 'label' => __('user.student.nis')],
            ['key' => 'profile.department.name', 'label' => __('user.student.department')],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::STUDENT->value)
            ->with(['profile.department']);
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
        return $query->when($this->filters['department_id'] ?? null, function ($q, $deptId) {
            $q->whereHas('profile', fn ($qp) => $qp->where('department_id', $deptId));
        });
    }

    #[Computed]
    public function departments()
    {
        return Department::orderBy('name')->get();
    }

    public function create(): void
    {
        $this->resetErrorBag();
        $this->form->reset();
        $this->userModal = true;
    }

    public function edit(string $id): void
    {
        $user = User::with('profile')->findOrFail($id);

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'national_id_number' => $user->profile?->national_id_number ?? '',
            'student_id_number' => $user->profile?->student_id_number ?? '',
            'department_id' => $user->profile?->department_id ?? '',
        ]);
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        $profileData = [
            'national_id_number' => $this->form->national_id_number,
            'student_id_number' => $this->form->student_id_number,
            'department_id' => $this->form->department_id,
        ];

        if ($this->form->id) {
            $user = User::findOrFail($this->form->id);
            $updateAction->execute($user, ['name' => $this->form->name, 'email' => $this->form->email], $profileData);
            flash()->success(__('user.student.success_updated'));
        } else {
            $createAction->execute(['name' => $this->form->name, 'email' => $this->form->email], $profileData, [RoleEnum::STUDENT->value]);
            flash()->success(__('user.student.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(string $id, DeleteUserAction $deleteAction): void
    {
        $user = User::findOrFail($id);

        $deleteAction->execute($user);
        flash()->success(__('user.student.success_deleted'));
    }

    public function deleteSelected(DeleteUserAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $user = User::find($id);
            if ($user) {
                $deleteAction->execute($user);
            }
        });
    }

    public function archiveAllFiltered(ArchiveStudentAccountsAction $action): void
    {
        $this->performMassAction('Archive Filtered', function ($query) use ($action) {
            $action->execute($query);
        });
    }

    public function render(): View
    {
        return view('admin.student-manager');
    }
}
