<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Actions\CreateUserAction;
use App\Domain\Admin\Actions\DeleteUserAction;
use App\Domain\Admin\Actions\UpdateUserAction;
use App\Domain\Auth\Enums\Role as RoleEnum;
use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Modernized Admin Manager using BaseRecordManager pattern.
 */
class AdminManager extends BaseRecordManager
{
    use AuthorizesRequests;

    public bool $userModal = false;

    public array $userData = [
        'id' => null,
        'name' => '',
        'email' => '',
        'roles' => [RoleEnum::ADMIN->value],
    ];

    public function boot(): void
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('super_admin')) {
            abort(403);
        }
    }

    /**
     * Define columns and sorting.
     */
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
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

    /**
     * Base query for admins.
     */
    protected function query(): Builder
    {
        return User::query()->role([RoleEnum::ADMIN->value, RoleEnum::SUPER_ADMIN->value]);
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
        $this->userData = [
            'id' => null,
            'name' => '',
            'email' => '',
            'roles' => [RoleEnum::ADMIN->value],
        ];
        $this->userModal = true;
    }

    public function edit(User $user): void
    {
        $this->authorize('update', $user);

        $this->resetErrorBag();
        $this->userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->roles->pluck('name')->toArray(),
        ];
        $this->userModal = true;
    }

    public function save(CreateUserAction $createAction, UpdateUserAction $updateAction): void
    {
        $this->validate([
            'userData.name' => 'required|string|max:255',
            'userData.email' => 'required|email|unique:users,email,'.($this->userData['id'] ?? 'NULL'),
        ]);

        if ($this->userData['id']) {
            $user = User::findOrFail($this->userData['id']);
            $this->authorize('update', $user);
            $updateAction->execute($user, $this->userData);
            flash()->success(__('user.admin.success_updated'));
        } else {
            $this->authorize('create', User::class);
            $createAction->execute($this->userData, [], $this->userData['roles']);
            flash()->success(__('user.admin.success_created'));
        }

        $this->userModal = false;
    }

    public function delete(User $user, DeleteUserAction $deleteAction): void
    {
        $this->authorize('delete', $user);

        if ($user->id === auth()->id()) {
            flash()->error('Cannot delete yourself.');

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
            if ($user) {
                $this->authorize('delete', $user);
                $deleteAction->execute($user);
            }
        });
    }

    public function render(): View
    {
        return view('admin.admin-manager');
    }
}
