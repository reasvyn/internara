<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Arr;
use Illuminate\View\View;
use Modules\Permission\Enums\Role;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Class UserManager
 *
 * General-purpose user viewer for SuperAdmins and Admins.
 *
 * Access control:
 * - SuperAdmin: can see all users + limited actions (suspend/delete).
 * - Admin: read-only view of users with role ≤ Admin plus role-less users
 *   (so orphaned/unauthorised accounts are visible for detection).
 *
 * Creation and full management happen in specialised managers
 * (StudentManager, TeacherManager, MentorManager, AdminManager).
 */
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
        $this->title    = __('user::ui.viewer.title');
        $this->subtitle = __('user::ui.viewer.subtitle');
        $this->context  = 'admin::ui.menu.users';

        // SuperAdmin can delete; Admin is read-only
        $this->viewPermission   = 'user.view';
        $this->deletePermission = 'user.manage';
    }

    /**
     * Define the table structure.
     */
    protected function getTableHeaders(): array
    {
        return [
            [
                'key'      => 'name',
                'label'    => __('user::ui.manager.table.name'),
                'sortable' => true,
                'format'   => fn (User $user): HtmlString => $this->renderNameCell($user),
            ],
            ['key' => 'email',    'label' => __('user::ui.manager.table.email'),    'sortable' => true],
            ['key' => 'username', 'label' => __('user::ui.manager.table.username'), 'sortable' => true],
            [
                'key'    => 'role_labels',
                'label'  => __('user::ui.manager.table.roles'),
                'format' => fn (User $user): HtmlString => $this->renderRoleBadgesCell($user),
            ],
            [
                'key'    => 'display_status',
                'label'  => __('user::ui.manager.table.status'),
                'format' => fn (User $user): HtmlString => $this->renderStatusBadgeCell($user),
            ],
            [
                'key'    => 'actions',
                'label'  => '',
                'class'  => 'w-1 text-right',
                'format' => fn (User $user): HtmlString => $this->renderActionsCell($user),
            ],
        ];
    }

    /**
     * Paginate visible users, annotated with computed display fields.
     */
    #[\Livewire\Attributes\Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $appliedFilters = array_filter(
            array_merge($this->filters, [
                'search'   => $this->search,
                'sort_by'  => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ]),
            fn ($v) => $v !== null && $v !== '' && $v !== [],
        );

        return $this->userQuery($appliedFilters)
            ->with(['roles:id,name', 'profile', 'statuses'])
            ->paginate($this->perPage)
            ->through(function (User $user): User {
                $roleNames = $user->roles->pluck('name')->values()->all();

                $displayStatus = $user->hasAnyRole([Role::SUPER_ADMIN->value, Role::ADMIN->value])
                    ? 'verified'
                    : ($user->latestStatus()?->name ?? User::STATUS_ACTIVE);

                $user->setAttribute('role_labels', implode(', ', $roleNames));
                $user->setAttribute('display_status', $displayStatus);

                return $user;
            });
    }

    /**
     * SuperAdmin-only: bulk delete selected users.
     */
    public function removeSelected(): void
    {
        $this->abortUnlessSuperAdmin();

        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $targets = $this->userQuery()
                ->whereIn('id', $this->selectedIds)
                ->get()
                ->reject(fn ($u) => $u->hasRole('super-admin'))
                ->pluck('id')
                ->toArray();

            $count = $this->service->destroy($targets);
            $this->selectedIds = [];
            flash()->success(__('user::ui.manager.deleted_successfully', ['count' => $count]));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function resetFilters(): void
    {
        $this->filters    = [];
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function activeFilterCount(): int
    {
        return count(array_filter(
            $this->filters,
            fn ($v) => $v !== null && $v !== '' && $v !== [],
        ));
    }

    // ─── Rendering helpers ────────────────────────────────────────────────────

    protected function renderNameCell(User $user): HtmlString
    {
        return new HtmlString(view('user::livewire.partials.user-manager-name-cell', [
            'user' => $user,
        ])->render());
    }

    protected function renderRoleBadgesCell(User $user): HtmlString
    {
        return new HtmlString(view('user::livewire.partials.user-manager-role-badges', [
            'user'    => $user,
            'manager' => $this,
        ])->render());
    }

    protected function renderStatusBadgeCell(User $user): HtmlString
    {
        return new HtmlString(view('user::livewire.partials.user-manager-status-badge', [
            'user'    => $user,
            'manager' => $this,
        ])->render());
    }

    protected function renderActionsCell(User $user): HtmlString
    {
        return new HtmlString(view('user::livewire.partials.user-manager-viewer-actions', [
            'user'         => $user,
            'isSuperAdmin' => auth()->user()?->hasRole(Role::SUPER_ADMIN->value),
        ])->render());
    }

    public function roleBadgeVariant(string $role): string
    {
        return match ($role) {
            Role::STUDENT->value    => 'primary',
            Role::TEACHER->value    => 'info',
            Role::MENTOR->value     => 'success',
            Role::ADMIN->value      => 'warning',
            Role::SUPER_ADMIN->value => 'error',
            default                 => 'secondary',
        };
    }

    public function statusBadgeVariant(string $status): string
    {
        return match ($status) {
            User::STATUS_ACTIVE   => 'success',
            'verified'            => 'info',
            User::STATUS_PENDING  => 'warning',
            User::STATUS_INACTIVE => 'error',
            default               => 'secondary',
        };
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('user::livewire.user-manager')
            ->layout('ui::components.layouts.dashboard', [
                'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
            ]);
    }

    // ─── Query ───────────────────────────────────────────────────────────────

    protected function userQuery(array $filters = []): Builder
    {
        $selectedRole   = $filters['role'] ?? null;
        $selectedStatus = $filters['status'] ?? null;
        $createdFrom    = $filters['created_from'] ?? null;
        $createdTo      = $filters['created_to'] ?? null;

        $query = $this->service->query(
            Arr::except($filters, ['role', 'status', 'created_from', 'created_to'])
        );

        $viewer = auth()->user();

        if ($viewer && ! $viewer->hasRole(Role::SUPER_ADMIN->value)) {
            // Admin: show only users with subordinate roles OR no roles at all.
            // SuperAdmin and Admin accounts are hidden from Admin viewers.
            $subordinateRoles = [Role::STUDENT->value, Role::TEACHER->value, Role::MENTOR->value];

            $query->where(function (Builder $q) use ($subordinateRoles): void {
                $q->whereHas('roles', fn (Builder $r) => $r->whereIn('name', $subordinateRoles))
                  ->orWhereDoesntHave('roles');
            })
            ->whereDoesntHave('roles', fn (Builder $r) => $r->whereIn('name', [
                Role::SUPER_ADMIN->value,
                Role::ADMIN->value,
            ]));
        }

        // Optional role filter (only effective roles visible to the viewer)
        $filterableRoles = $viewer?->hasRole(Role::SUPER_ADMIN->value)
            ? array_column(Role::cases(), 'value')
            : [Role::STUDENT->value, Role::TEACHER->value, Role::MENTOR->value];

        if ($selectedRole && in_array($selectedRole, $filterableRoles, true)) {
            if ($selectedRole === 'no_role') {
                $query->whereDoesntHave('roles');
            } else {
                $query->whereHas('roles', fn (Builder $r) => $r->where('name', $selectedRole));
            }
        }

        if (in_array($selectedStatus, [User::STATUS_ACTIVE, User::STATUS_INACTIVE, User::STATUS_PENDING], true)) {
            $this->applyLatestStatusFilter($query, $selectedStatus);
        }

        if ($createdFrom) {
            $query->whereDate((new User)->getTable().'.created_at', '>=', $createdFrom);
        }

        if ($createdTo) {
            $query->whereDate((new User)->getTable().'.created_at', '<=', $createdTo);
        }

        return $query;
    }

    protected function applyLatestStatusFilter(Builder $query, string $status): void
    {
        $statusTable = app(config('model-status.status_model'))->getTable();
        $userTable   = (new User)->getTable();

        $query->whereExists(function ($sub) use ($status, $statusTable, $userTable): void {
            $sub->selectRaw('1')
                ->from($statusTable.' as latest_status')
                ->whereColumn('latest_status.model_id', $userTable.'.id')
                ->where('latest_status.model_type', User::class)
                ->where('latest_status.name', $status)
                ->whereRaw(
                    'latest_status.created_at = (select max(s2.created_at) from '.$statusTable.' as s2 where s2.model_type = ? and s2.model_id = '.$userTable.'.id)',
                    [User::class],
                );
        });
    }

    // ─── Guards ──────────────────────────────────────────────────────────────

    private function abortUnlessSuperAdmin(): void
    {
        abort_unless(
            auth()->user()?->hasRole(Role::SUPER_ADMIN->value),
            403,
            __('user::exceptions.super_admin_unauthorized'),
        );
    }
}
