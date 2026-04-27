<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Modules\Permission\Enums\Role;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

class UserManager extends RecordManager
{
    /**
     * Initialize the component metadata and services.
     */
    public function boot(UserService $userService): void
    {
        $this->service = $userService;
        $this->eventPrefix = 'user';
        $this->modelClass = User::class;
    }

    /**
     * Configure the component's basic properties.
     */
    public function initialize(): void
    {
        $this->title = __('user::ui.viewer.title');
        $this->subtitle = __('user::ui.viewer.subtitle');
        $this->context = 'admin::ui.menu.users';

        // SuperAdmin can delete; Admin is read-only
        $this->viewPermission = 'user.view';
        $this->deletePermission = 'user.manage';

        $this->searchable = ['name', 'email', 'username'];
        $this->sortable = ['name', 'email', 'username', 'created_at'];
    }

    /**
     * UserManager is read-only for creation and update.
     */
    public function can(string $action, mixed $target = null): bool
    {
        if (in_array($action, ['create', 'update'], true)) {
            return false;
        }

        return parent::can($action, $target);
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('user::ui.manager.table.name'), 'sortable' => true],
            ['key' => 'email', 'label' => __('user::ui.manager.table.email'), 'sortable' => true],
            [
                'key' => 'username',
                'label' => __('user::ui.manager.table.username'),
                'sortable' => true,
            ],
            ['key' => 'role_labels', 'label' => __('user::ui.manager.table.roles')],
            ['key' => 'display_status', 'label' => __('user::ui.manager.table.status')],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1 text-right'],
        ];
    }

    /**
     * Transform raw user record for UI display.
     */
    protected function mapRecord(mixed $record): array
    {
        $roleNames = $record->roles->pluck('name')->values()->all();

        $displayStatus = $record->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value])
            ? 'verified'
            : $record->latestStatus()?->name ?? User::STATUS_ACTIVE;

        return array_merge($record->toArray(), [
            'avatar_url' => $record->avatar_url,
            'role_labels' => $roleNames,
            'display_status' => $displayStatus,
        ]);
    }

    /**
     * Fetch and transform records for the table.
     */
    #[Computed]
    public function records(): LengthAwarePaginator
    {
        return $this->userQuery($this->filters)
            ->with(['roles:id,name', 'profile', 'statuses'])
            ->paginate($this->perPage)
            ->through(fn($user) => $this->mapRecord($user));
    }

    /**
     * Reset all applied filters and pagination.
     */
    public function resetFilters(): void
    {
        $this->filters = [];
        $this->selectedIds = [];
        $this->resetPage();
    }

    /**
     * Count the number of active filters.
     */
    public function activeFilterCount(): int
    {
        return count(array_filter($this->filters, fn($v) => $v !== null && $v !== '' && $v !== []));
    }

    public function roleBadgeVariant(string $role): string
    {
        return match ($role) {
            Role::STUDENT->value => 'primary',
            Role::TEACHER->value => 'info',
            Role::MENTOR->value => 'success',
            Role::ADMIN->value => 'warning',
            Role::SUPER_ADMIN->value => 'error',
            default => 'neutral',
        };
    }

    public function statusBadgeVariant(string $status): string
    {
        return match ($status) {
            User::STATUS_ACTIVE => 'success',
            'verified' => 'info',
            User::STATUS_PENDING => 'warning',
            User::STATUS_INACTIVE => 'error',
            default => 'neutral',
        };
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('user::livewire.user-manager');
    }

    // ─── Query Logic ────────────────────────────────────────────────────────

    protected function userQuery(array $filters = []): Builder
    {
        $selectedRole = $filters['role'] ?? null;
        $selectedStatus = $filters['status'] ?? null;
        $createdFrom = $filters['created_from'] ?? null;
        $createdTo = $filters['created_to'] ?? null;

        $query = $this->service->query(
            Arr::except($filters, ['role', 'status', 'created_from', 'created_to']),
        );

        $viewer = auth()->user();

        if ($viewer && !$viewer->hasRole(Role::SUPER_ADMIN->value)) {
            // Admin: show only students, teachers, and mentors
            $subordinateRoles = [Role::STUDENT->value, Role::TEACHER->value, Role::MENTOR->value];

            $query
                ->where(function (Builder $q) use ($subordinateRoles): void {
                    $q->whereHas(
                        'roles',
                        fn(Builder $r) => $r->whereIn('name', $subordinateRoles),
                    )->orWhereDoesntHave('roles');
                })
                ->whereDoesntHave(
                    'roles',
                    fn(Builder $r) => $r->whereIn('name', [
                        Role::SUPER_ADMIN->value,
                        Role::ADMIN->value,
                    ]),
                );
        }

        // Apply filters
        if ($selectedRole) {
            if ($selectedRole === 'no_role') {
                $query->whereDoesntHave('roles');
            } else {
                $query->whereHas('roles', fn(Builder $r) => $r->where('name', $selectedRole));
            }
        }

        if (
            in_array(
                $selectedStatus,
                [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_PENDING],
                true,
            )
        ) {
            $this->applyLatestStatusFilter($query, $selectedStatus);
        }

        if ($createdFrom) {
            $query->whereDate(new User()->getTable() . '.created_at', '>=', $createdFrom);
        }

        if ($createdTo) {
            $query->whereDate(new User()->getTable() . '.created_at', '<=', $createdTo);
        }

        return $query;
    }

    protected function applyLatestStatusFilter(Builder $query, string $status): void
    {
        $statusTable = app(config('model-status.status_model'))->getTable();
        $userTable = new User()->getTable();

        $query->whereExists(function ($sub) use ($status, $statusTable, $userTable): void {
            $sub->selectRaw('1')
                ->from($statusTable . ' as latest_status')
                ->whereColumn('latest_status.model_id', $userTable . '.id')
                ->where('latest_status.model_type', User::class)
                ->where('latest_status.name', $status)
                ->whereRaw(
                    'latest_status.created_at = (select max(s2.created_at) from ' .
                        $statusTable .
                        ' as s2 where s2.model_type = ? and s2.model_id = ' .
                        $userTable .
                        '.id)',
                    [User::class],
                );
        });
    }
}
