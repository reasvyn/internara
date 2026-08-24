<?php

declare(strict_types=1);

namespace App\User\UserManagement\Livewire;

use App\Auth\Permissions\Enums\Role as RoleEnum;
use App\Core\Exceptions\RejectedException;
use App\Core\Livewire\BaseRecordManager;
use App\Core\Support\CsvHandler;
use App\User\Models\User;
use App\User\UserManagement\Actions\CreateUserAction;
use App\User\UserManagement\Actions\DeleteUserAction;
use App\User\UserManagement\Actions\UpdateUserAction;
use App\User\UserManagement\Data\CreateUserData;
use App\User\UserManagement\Data\UpdateUserData;
use App\User\UserManagement\Livewire\Forms\AdminUserForm;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Symfony\Component\HttpFoundation\StreamedResponse;
use TallStackUi\Traits\Interactions;

class AdminManager extends BaseRecordManager
{
    use AuthorizesRequests;
    use Interactions;

    public bool $userModal = false;

    public string $confirmActionType = '';

    public ?string $confirmTarget = null;

    public AdminUserForm $form;

    public function boot(): void
    {
        $this->authorize('viewAdmin', User::class);
    }

    public function headers(): array
    {
        return [
            ['index' => 'name', 'label' => __('user.admin.name'), 'sortable' => true],
            ['index' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            [
                'index' => 'username',
                'label' => __('user.fields.username'),
                'class' => 'font-mono text-xs',
            ],
            ['index' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['index' => 'actions', 'label' => '', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query()->role([RoleEnum::ADMIN->value, RoleEnum::SUPER_ADMIN->value]);
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
                $this->filters['setup_required'] ?? null,
                fn ($q, $v) => $q->where('setup_required', $v === 'yes'),
            )
            ->when(
                $this->filters['locked'] ?? null,
                fn ($q, $v) => $v === 'yes'
                    ? $q->whereNotNull('locked_at')
                    : $q->whereNull('locked_at'),
            );
    }

    // --- Record Actions ---

    public function create(): void
    {
        $this->authorize('create', User::class);

        $this->resetErrorBag();
        $this->form->reset();
        $this->form->roles = [RoleEnum::ADMIN->value];
        $this->userModal = true;
    }

    public function edit(string $id): void
    {
        $user = User::with('roles')->findOrFail($id);

        if ($user->hasRole('super_admin')) {
            $this->toast()->error(__('user.admin.cannot_edit'))->send();

            return;
        }

        $this->resetErrorBag();
        $this->form->fill([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
        ]);
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->form->validate();

        if ($this->form->id) {
            $user = User::findOrFail($this->form->id);
            $this->authorize('update', $user);
            $updateAction->execute(new UpdateUserData(
                userId: $user->id,
                user: [
                    'name' => $this->form->name,
                    'email' => $this->form->email,
                ],
            ));
            $this->toast()->success(__('user.admin.success_updated'))->send();
        } else {
            $this->authorize('create', User::class);
            $createAction->execute(new CreateUserData(
                user: ['name' => $this->form->name, 'email' => $this->form->email],
                profile: [],
                roles: $this->form->roles,
            ));
            $this->toast()->success(__('user.admin.success_created'))->send();
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
                $user = User::findOrFail($this->confirmTarget);

                if ($user->hasRole('super_admin')) {
                    $this->toast()->error(__('user.admin.cannot_delete'))->send();

                    return;
                }

                if ($user->id === auth()->id()) {
                    $this->toast()->error(__('user.admin.cannot_delete_self'))->send();

                    return;
                }

                $deleteAction->execute($user);
                $this->toast()->success(__('user.admin.success_deleted'))->send();
            } elseif ($this->confirmActionType === 'deleteSelected') {
                $this->performBulkAction(__('common.actions.delete'), function ($id) use ($deleteAction) {
                    if ($id === auth()->id()) {
                        return;
                    }
                    $user = User::find($id);
                    if ($user && ! $user->hasRole('super_admin')) {
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

    public function export(CsvHandler $csv): StreamedResponse
    {
        $users = User::query()
            ->role([RoleEnum::ADMIN->value, RoleEnum::SUPER_ADMIN->value])
            ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();

        return $csv->export(
            $users,
            [__('user.fields.full_name'), __('user.fields.email'), __('user.fields.username')],
            fn ($u) => [$u->name, $u->email, $u->username],
            'admins.csv',
        );
    }

    public function render(): View
    {
        return view('user.user-management.admin-manager');
    }
}
