<?php

declare(strict_types=1);

namespace App\SysAdmin\Account\Livewire;

use App\Auth\Permissions\Enums\Role as RoleEnum;
use App\Core\Livewire\BaseRecordManager;
use App\SysAdmin\Account\Actions\CreateUserAction;
use App\SysAdmin\Account\Actions\DeleteUserAction;
use App\SysAdmin\Account\Actions\UpdateUserAction;
use App\SysAdmin\Account\Livewire\Forms\AdminUserForm;
use App\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class AdminManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $userModal = false;

    public AdminUserForm $form;

    public function boot(): void
    {
        $this->authorize('viewAdmin', User::class);
    }

    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => __('user.admin.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user.fields.email'), 'sortable' => true],
            [
                'key' => 'username',
                'label' => __('user.fields.username'),
                'class' => 'font-mono text-xs',
            ],
            ['key' => 'created_at', 'label' => __('user.student.joined'), 'sortable' => true],
            ['key' => 'actions', 'label' => '', 'sortable' => false],
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
            ->when($this->filters['setup_required'] ?? null, fn ($q, $v) => $q->where('setup_required', $v === 'yes'))
            ->when($this->filters['locked'] ?? null, fn ($q, $v) => $v === 'yes' ? $q->whereNotNull('locked_at') : $q->whereNull('locked_at'));
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
            flash()->error(__('user.admin.cannot_edit'));

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
            $updateAction->execute($user, ['name' => $this->form->name, 'email' => $this->form->email]);
            flash()->success(__('user.admin.success_updated'));
        } else {
            $this->authorize('create', User::class);
            $createAction->execute(['name' => $this->form->name, 'email' => $this->form->email], [], $this->form->roles);
            flash()->success(__('user.admin.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(string $id, DeleteUserAction $deleteAction): void
    {
        $user = User::findOrFail($id);

        if ($user->hasRole('super_admin')) {
            flash()->error(__('user.admin.cannot_delete'));

            return;
        }

        if ($user->id === auth()->id()) {
            flash()->error(__('user.admin.cannot_delete_self'));

            return;
        }

        $deleteAction->execute($user);
        flash()->success(__('user.admin.success_deleted'));
    }

    // --- Bulk Actions ---

    public function deleteSelected(DeleteUserAction $deleteAction): void
    {
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

    public function render(): View
    {
        return view('sysadmin.account.admin-manager');
    }
}
