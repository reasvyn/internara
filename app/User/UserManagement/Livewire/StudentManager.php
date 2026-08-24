<?php

declare(strict_types=1);

namespace App\User\UserManagement\Livewire;

use App\Academics\Department\Models\Department;
use App\Auth\Permissions\Enums\Role as RoleEnum;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Support\CsvHandler;
use App\User\Models\User;
use App\User\UserManagement\Actions\CreateUserAction;
use App\User\UserManagement\Actions\DeleteUserAction;
use App\User\UserManagement\Actions\DispatchArchiveStudentAccountsAction;
use App\User\UserManagement\Actions\UpdateUserAction;
use App\User\UserManagement\Data\CreateUserData;
use App\User\UserManagement\Data\UpdateUserData;
use App\User\UserManagement\Livewire\Concerns\DownloadsAccountSlips;
use App\User\UserManagement\Livewire\Forms\StudentForm;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TallStackUi\Traits\Interactions;

class StudentManager extends BaseRecordManager
{
    use AuthorizesRequests, DownloadsAccountSlips, WithFileUploads;
    use Interactions;

    public bool $userModal = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public StudentForm $form;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function headers(): array
    {
        return [
            ['index' => 'name', 'label' => __('user.student.name'), 'sortable' => true],
            [
                'index' => 'username',
                'label' => __('user.student.username'),
                'class' => 'font-mono text-xs',
            ],
            ['index' => 'profile.national_id_number', 'label' => __('user.student.nisn')],
            ['index' => 'profile.id_number', 'label' => __('user.student.nis')],
            ['index' => 'profile.department.name', 'label' => __('user.student.department')],
            ['index' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
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
        return $query
            ->when(
                $this->filters['department_id'] ?? null,
                fn ($q, $deptId) => $q->whereHas(
                    'profile',
                    fn ($qp) => $qp->where('department_id', $deptId),
                ),
            )
            ->when(
                $this->filters['created_from'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '>=', $v),
            )
            ->when(
                $this->filters['created_to'] ?? null,
                fn ($q, $v) => $q->whereDate('created_at', '<=', $v),
            );
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
            'id_number' => $user->profile?->id_number ?? '',
            'department_id' => $user->profile?->department_id ?? '',
        ]);
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        $profileData = [
            'national_id_number' => $this->form->national_id_number,
            'id_number' => $this->form->id_number,
            'department_id' => $this->form->department_id,
        ];

        if ($this->form->id) {
            $user = User::findOrFail($this->form->id);
            $updateAction->execute(new UpdateUserData(
                userId: $user->id,
                user: ['name' => $this->form->name, 'email' => $this->form->email],
                profile: $profileData,
            ));
            $this->toast()->success(__('user.student.success_updated'))->send();
        } else {
            $user = $createAction->execute(new CreateUserData(
                user: ['name' => $this->form->name, 'email' => $this->form->email],
                profile: $profileData,
                roles: [RoleEnum::STUDENT->value],
                sendNotification: false,
            ));
            $this->userModal = false;
            $this->redirect(route('admin.users.account-slip', $user));

            return;
        }

        $this->userModal = false;
    }

    public function askDelete(string $id): void
    {
        $this->confirmActionType = 'delete';
        $this->confirmTarget = $id;
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function askDeleteSelected(): void
    {
        $this->confirmActionType = 'deleteSelected';
        $this->dialog()
            ->question(__('common.actions.confirm_action'), $this->confirmMessage ?? __('common.actions.confirm_message'))
            ->confirm(text: __('common.actions.confirm'), method: 'confirmAction')
            ->cancel(text: __('common.actions.cancel'))
            ->send();
    }

    public function confirmAction(DeleteUserAction $deleteAction): void
    {
        try {
            if ($this->confirmActionType === 'delete') {
                $deleteAction->execute(User::findOrFail($this->confirmTarget));
                $this->toast()->success(__('user.student.success_deleted'))->send();
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
                    $user = User::find($id);
                    if ($user) {
                        $deleteAction->execute($user);
                    }
                });
            }
        } catch (RejectedException $e) {
            $this->toast()->error($e->getMessage())->send();
        }
        $this->confirmTarget = null;
        $this->confirmActionType = '';
    }

    public function archiveAllFiltered(DispatchArchiveStudentAccountsAction $action): void
    {
        $this->performMassAction(__('user.manager.archive_filtered'), function ($query) use ($action) {
            $action->execute($query);
        });
    }

    public function export(CsvHandler $csv): StreamedResponse
    {
        $users = User::query()
            ->role(RoleEnum::STUDENT->value)
            ->with('profile.department')
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $users,
            [
                __('user.fields.full_name'),
                __('user.fields.email'),
                __('user.student.nisn'),
                __('user.student.nis'),
            ],
            fn ($u) => [
                $u->name,
                $u->email,
                $u->profile?->national_id_number ?? '',
                $u->profile?->id_number ?? '',
            ],
            'students.csv',
        );
    }

    public function getIdNumberLabel(): string
    {
        return __('user.student.nis');
    }

    public function render(): View
    {
        return view('user.user-management.student-manager');
    }
}
