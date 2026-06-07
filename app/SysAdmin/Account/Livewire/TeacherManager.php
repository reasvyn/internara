<?php

declare(strict_types=1);

namespace App\SysAdmin\Account\Livewire;

use App\Auth\Permissions\Enums\Role as RoleEnum;
use App\Core\Livewire\BaseRecordManager;
use App\SysAdmin\Account\Actions\CreateUserAction;
use App\SysAdmin\Account\Actions\DeleteUserAction;
use App\SysAdmin\Account\Actions\UpdateUserAction;
use App\SysAdmin\Account\Livewire\Concerns\DownloadsAccountSlips;
use App\SysAdmin\Account\Livewire\Forms\TeacherForm;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class TeacherManager extends BaseRecordManager
{
    use AuthorizesRequests, DownloadsAccountSlips;

    public bool $userModal = false;

    public TeacherForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.teacher.name'), 'sortable' => true],
            [
                'key' => 'username',
                'label' => __('user.fields.username'),
                'class' => 'font-mono text-xs',
            ],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            [
                'key' => 'profile.employee_id_number',
                'label' => __('user.teacher.employee_id_number'),
            ],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()
            ->role(RoleEnum::TEACHER->value)
            ->with(['profile']);
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
            ->when(
                $this->filters['created_from'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '>=', $v),
            )
            ->when(
                $this->filters['created_to'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '<=', $v),
            );
    }

    // --- Record Actions ---

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
            'employee_id_number' => $user->profile?->employee_id_number ?? '',
        ]);
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        $profileData = [
            'employee_id_number' => $this->form->employee_id_number,
        ];

        if ($this->form->id) {
            $user = User::findOrFail($this->form->id);
            $updateAction->execute(
                $user,
                ['name' => $this->form->name, 'email' => $this->form->email],
                $profileData,
            );
            flash()->success(__('user.teacher.success_updated'));
        } else {
            $user = $createAction->execute(
                ['name' => $this->form->name, 'email' => $this->form->email],
                $profileData,
                [RoleEnum::TEACHER->value],
                false,
            );
            $this->userModal = false;
            $this->redirect(route('sysadmin.users.account-slip', $user));

            return;
        }

        $this->userModal = false;
    }

    public function delete(string $id, DeleteUserAction $deleteAction): void
    {
        $user = User::findOrFail($id);

        $deleteAction->execute($user);
        flash()->success(__('user.teacher.success_deleted'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeleteUserAction $deleteAction): void
    {
        $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
            $user = User::find($id);
            if ($user) {
                $deleteAction->execute($user);
            }
        });
    }

    public function render(): View
    {
        return view('sysadmin.account.teacher-manager');
    }
}
