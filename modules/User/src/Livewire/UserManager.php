<?php

declare(strict_types=1);

namespace Modules\User\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Department\Models\Department;
use Modules\Permission\Enums\Role;
use Modules\Department\Livewire\Concerns\HasDepartmentOptions;
use Modules\UI\Livewire\RecordManager;
use Modules\User\Livewire\Forms\UserManagerForm;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Class UserManager
 *
 * Provides a unified interface for managing system users, supporting role-based filtering
 * and standard CRUD operations via the RecordManager abstraction.
 */
class UserManager extends RecordManager
{
    use HasDepartmentOptions;

    /**
     * Operational roles managed by this manager.
     *
     * @var list<string>
     */
    private const MANAGED_ROLES = [
        Role::STUDENT->value,
        Role::TEACHER->value,
        Role::MENTOR->value,
    ];

    public UserManagerForm $form;

    /**
     * The specific role being managed (optional).
     */
    public ?string $targetRole = null;

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
        $roleKey = $this->targetRole ?: 'user';
        $this->title = $this->targetRole
            ? __("user::ui.{$roleKey}_management")
            : __('user::ui.manager.title');
        $this->subtitle = __('user::ui.manager.subtitle');
        $this->context = 'admin::ui.menu.users';
        $this->addLabel = __('user::ui.manager.add_'.$roleKey);
        $this->deleteConfirmMessage = __('user::ui.manager.delete.message');

        $this->viewPermission = 'user.view';
        $this->createPermission = 'user.manage';
        $this->updatePermission = 'user.manage';
        $this->deletePermission = 'user.manage';
        $this->importInstructions = __('user::ui.manager.import.instructions');
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
            ['key' => 'actions', 'label' => '', 'class' => 'w-1'],
        ];
    }

    /**
     * Customize the query to include roles and profiles.
     */
    #[\Livewire\Attributes\Computed]
    public function records(): \Illuminate\Pagination\LengthAwarePaginator
    {
        $appliedFilters = array_filter(
            array_merge($this->filters, [
                'search' => $this->search,
                'sort_by' => $this->sortBy['column'] ?? 'created_at',
                'sort_dir' => $this->sortBy['direction'] ?? 'desc',
            ]),
            fn ($value) => $value !== null && $value !== '' && $value !== [],
        );

        return $this->managedUserQuery($appliedFilters)
            ->with(['roles:id,name', 'profile.department', 'statuses'])
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
     * Remove all selected users with safety checks.
     */
    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            return;
        }

        try {
            $targets = $this->managedUserQuery()
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

    public function sendPasswordResetLink(mixed $id): void
    {
        try {
            $this->service->sendPasswordResetLink($id);
            flash()->success(__('auth::ui.forgot_password.sent'));
        } catch (\Throwable $e) {
            flash()->error($e->getMessage());
        }
    }

    public function resetFilters(): void
    {
        $this->filters = [];
        $this->selectedIds = [];
        $this->resetPage();
    }

    public function activeFilterCount(): int
    {
        return count(array_filter(
            $this->filters,
            fn ($value) => $value !== null && $value !== '' && $value !== [],
        ));
    }

    public function save(): void
    {
        $this->form->validate();
        $payload = Arr::except($this->form->all(), ['password', 'password_confirmation']);
        $isSetupAuthorized = session(\Modules\Setup\Services\Contracts\SetupService::SESSION_SETUP_AUTHORIZED) === true;

        try {
            if ($isSetupAuthorized) {
                $this->service->withoutAuthorization();
            }

            if ($this->form->id) {
                $record = $this->service->find($this->form->id);
                if (! $isSetupAuthorized && $record && $this->updatePermission) {
                    \Illuminate\Support\Facades\Gate::authorize($this->updatePermission, $record);
                }
                $this->service->update($this->form->id, $payload);
            } else {
                if (! $isSetupAuthorized && $this->createPermission) {
                    $roles = $this->form->roles;
                    $authModel = $this->modelClass ?: config('auth.providers.users.model');
                    \Illuminate\Support\Facades\Gate::authorize($this->createPermission, [$authModel, $roles]);
                }
                $this->service->create($payload);
            }

            $this->toggleModal(self::MODAL_FORM, false);
            flash()->success('shared::messages.record_saved');
            $this->dispatch($this->getEventPrefix().':saved', exists: true);
        } catch (\Throwable $e) {
            if (is_debug_mode()) {
                throw $e;
            }

            flash()->error($e->getMessage());
        }
    }

    /**
     * Prepare form for a new user, pre-assigning target role if applicable.
     */
    public function add(): void
    {
        $this->form->reset();

        if ($this->targetRole && in_array($this->targetRole, self::MANAGED_ROLES, true)) {
            $this->form->roles = [$this->targetRole];
        }

        $this->toggleModal(self::MODAL_FORM, true);
    }

    protected function getExportHeaders(): array
    {
        return [
            'name' => __('user::ui.manager.table.name'),
            'email' => __('user::ui.manager.table.email'),
            'username' => __('user::ui.manager.table.username'),
            'roles' => __('user::ui.manager.import.columns.roles'),
            'status' => __('user::ui.manager.table.status'),
            'department_name' => __('user::ui.manager.import.columns.department_name'),
            'phone' => __('user::ui.manager.form.phone'),
            'address' => __('user::ui.manager.form.address'),
            'gender' => __('user::ui.manager.form.gender'),
            'national_identifier' => __('user::ui.manager.import.columns.national_identifier'),
            'registration_number' => __('user::ui.manager.import.columns.registration_number'),
        ];
    }

    protected function getTemplateHeaders(): array
    {
        return $this->getExportHeaders();
    }

    protected function getExportQuery(): Builder
    {
        return $this->managedUserQuery()->with(['roles:id,name', 'profile.department', 'statuses']);
    }

    protected function mapRecordForExport($record, array $keys): array
    {
        $roles = $record->roles->pluck('name')->intersect(self::MANAGED_ROLES)->values()->implode(', ');
        $profile = $record->profile;

        return [
            $record->name,
            $record->email,
            $record->username,
            $roles,
            $record->latestStatus()?->name ?? User::STATUS_ACTIVE,
            $profile?->department?->name ?? '',
            $profile?->phone ?? '',
            $profile?->address ?? '',
            $profile?->gender ?? '',
            $profile?->national_identifier ?? '',
            $profile?->registration_number ?? '',
        ];
    }

    protected function mapImportRow(array $row, array $keys): ?array
    {
        $data = [];
        foreach ($keys as $index => $key) {
            $value = $row[$index] ?? null;
            $data[$key] = is_string($value) ? trim($value) : $value;
        }

        if (blank(implode('', array_filter($data, fn ($value) => $value !== null)))) {
            return null;
        }

        $roles = $this->targetRole && in_array($this->targetRole, self::MANAGED_ROLES, true)
            ? [$this->targetRole]
            : $this->normalizeRoles($data['roles'] ?? null);

        if ($roles === []) {
            $roles = [Role::STUDENT->value];
        }

        $status = Str::lower((string) ($data['status'] ?? 'pending'));
        if (! in_array($status, ['active', 'inactive', 'pending'], true)) {
            $status = 'pending';
        }

        return [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'username' => $data['username'] ?? null,
            'roles' => $roles,
            'status' => $status,
            'profile' => array_filter([
                'department_id' => $this->resolveDepartmentId($data['department_name'] ?? null),
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'gender' => $this->normalizeGender($data['gender'] ?? null),
                'national_identifier' => $data['national_identifier'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
            ], fn ($value) => $value !== null && $value !== ''),
        ];
    }

    /**
     * Build the operational user query and exclude privileged accounts.
     */
    protected function managedUserQuery(array $filters = []): Builder
    {
        $selectedRole = $filters['role'] ?? null;
        $selectedStatus = $filters['status'] ?? null;
        $createdFrom = $filters['created_from'] ?? null;
        $createdTo = $filters['created_to'] ?? null;

        $roles = $this->targetRole && in_array($this->targetRole, self::MANAGED_ROLES, true)
            ? [$this->targetRole]
            : self::MANAGED_ROLES;

        $query = $this->service
            ->query(Arr::except($filters, ['role', 'status', 'created_from', 'created_to']))
            ->whereHas('roles', fn (Builder $query): Builder => $query->whereIn('name', $roles))
            ->whereDoesntHave('roles', fn (Builder $query): Builder => $query->whereIn('name', [
                Role::SUPER_ADMIN->value,
                Role::ADMIN->value,
            ]));

        if (! $this->targetRole && in_array($selectedRole, self::MANAGED_ROLES, true)) {
            $query->whereHas('roles', fn (Builder $roleQuery): Builder => $roleQuery->where('name', $selectedRole));
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
        $userTable = (new User)->getTable();

        $query->whereExists(function ($statusQuery) use ($status, $statusTable, $userTable): void {
            $statusQuery
                ->selectRaw('1')
                ->from($statusTable.' as latest_status')
                ->whereColumn('latest_status.model_id', $userTable.'.id')
                ->where('latest_status.model_type', User::class)
                ->where('latest_status.name', $status)
                ->whereRaw(
                    'latest_status.created_at = (select max(status_history.created_at) from '.$statusTable.' as status_history where status_history.model_type = ? and status_history.model_id = '.$userTable.'.id)',
                    [User::class],
                );
        });
    }

    public function roleBadgeVariant(string $role): string
    {
        return match ($role) {
            Role::STUDENT->value => 'success',
            Role::TEACHER->value => 'info',
            Role::MENTOR->value => 'warning',
            Role::ADMIN->value => 'primary',
            Role::SUPER_ADMIN->value => 'error',
            default => 'secondary',
        };
    }

    public function statusBadgeVariant(string $status): string
    {
        return match ($status) {
            User::STATUS_ACTIVE, 'verified' => 'success',
            User::STATUS_PENDING => 'warning',
            User::STATUS_INACTIVE => 'secondary',
            default => 'secondary',
        };
    }

    /**
     * Normalize imported role labels into operational system roles.
     *
     * @return list<string>
     */
    protected function normalizeRoles(?string $rawRoles): array
    {
        return collect(explode(',', (string) $rawRoles))
            ->map(fn (string $role): string => Str::lower(trim($role)))
            ->filter()
            ->intersect(self::MANAGED_ROLES)
            ->values()
            ->all();
    }

    protected function normalizeGender(?string $gender): ?string
    {
        $normalized = Str::lower(trim((string) $gender));

        return in_array($normalized, ['male', 'female'], true) ? $normalized : null;
    }

    protected function resolveDepartmentId(?string $departmentName): ?string
    {
        $name = trim((string) $departmentName);
        if ($name === '') {
            return null;
        }

        /** @var Department|null $department */
        $department = Department::query()
            ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
            ->first();

        return $department?->id;
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        $roleKey = $this->targetRole ?: 'user';

        return view('user::livewire.user-manager', [
            'roleKey' => $roleKey,
        ])->layout('ui::components.layouts.dashboard', [
            'title' => $this->title.' | '.setting('brand_name', setting('app_name')),
        ]);
    }
}
