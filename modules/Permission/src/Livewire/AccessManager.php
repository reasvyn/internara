<?php

declare(strict_types=1);

namespace Modules\Permission\Livewire;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Modules\Permission\Enums\Permission;
use Modules\Permission\Enums\Role;
use Modules\Permission\Models\Permission as PermissionModel;
use Modules\Permission\Models\Role as RoleModel;
use Modules\Permission\Services\AccessManagementService;
use Modules\UI\Livewire\RecordManager;

/**
 * Livewire component for managing roles and permissions.
 *
 * Security model:
 * - Super Admin: can manage ALL roles and permissions
 * - Admin: can only manage subordinate roles (student, teacher, mentor)
 */
class AccessManager extends RecordManager
{
    protected AccessManagementService $accessService;

    /**
     * Boot the component with services.
     */
    public function boot(AccessManagementService $accessService): void
    {
        $this->service = $accessService;
        $this->eventPrefix = 'access';
        $this->modelClass = RoleModel::class;
    }

    /**
     * Initialize component metadata.
     */
    public function initialize(): void
    {
        $this->title = __('permission::ui.access_manager.title');
        $this->subtitle = __('permission::ui.access_manager.subtitle');
        $this->context = 'permission::ui.menu.access';

        $this->viewPermission = Permission::USER_MANAGE;
        $this->searchable = ['name', 'description'];
        $this->sortable = ['name', 'created_at'];
    }

    /**
     * Check if current user can manage specific role.
     */
    public function canManageRole(string $roleName): bool
    {
        $user = auth()->user();

        if ($user->hasRole(Role::SUPER_ADMIN->value)) {
            return true;
        }

        // Admin can only manage subordinate roles
        return in_array($roleName, [
            Role::TEACHER->value,
            Role::MENTOR->value,
            Role::STUDENT->value,
        ], true);
    }

    /**
     * Get permissions grouped by module for modal display.
     */
    public function permissionsByModule(): array
    {
        return PermissionModel::query()
            ->orderBy('module')
            ->orderBy('name')
            ->get()
            ->groupBy('module')
            ->map(fn ($perms) => $perms->pluck('name')->values()->all())
            ->toArray();
    }

    /**
     * Get current permissions for a role.
     */
    public function getCurrentPermissions(string $roleId): array
    {
        $role = RoleModel::find($roleId);

        return $role?->permissions->pluck('name')->values()->all() ?? [];
    }

    /**
     * Toggle permission for a role.
     */
    #[On('toggle-permission')]
    public function togglePermission(string $roleId, string $permissionName): void
    {
        $role = RoleModel::findOrFail($roleId);

        if (! $this->canManageRole($role->name)) {
            $this->notify(__('permission::ui.access_manager.cannot_manage'), 'error');

            return;
        }

        $currentPermissions = $role->permissions->pluck('name')->values()->all();

        if (in_array($permissionName, $currentPermissions, true)) {
            $newPermissions = array_values(array_diff($currentPermissions, [$permissionName]));
        } else {
            $newPermissions = array_merge($currentPermissions, [$permissionName]);
        }

        $this->accessService->assignPermissionsToRole($role->name, $newPermissions);

        $this->notify(__('permission::ui.access_manager.saved'), 'success');
    }

    /**
     * Save (replace) all permissions for a role.
     */
    public function saveRolePermissions(string $roleId, array $permissionNames): void
    {
        $role = RoleModel::findOrFail($roleId);

        if (! $this->canManageRole($role->name)) {
            $this->notify(__('permission::ui.access_manager.cannot_manage'), 'error');

            return;
        }

        $this->accessService->assignPermissionsToRole($role->name, $permissionNames);

        $this->notify(__('permission::ui.access_manager.saved'), 'success');
        $this->closeModal();
    }

    /**
     * Define table columns.
     */
    protected function getTableHeaders(): array
    {
        return [
            ['key' => 'name', 'label' => __('permission::ui.access_manager.table.role'), 'sortable' => true],
            ['key' => 'description', 'label' => __('permission::ui.access_manager.table.description')],
            ['key' => 'permission_count', 'label' => __('permission::ui.access_manager.table.permissions')],
            ['key' => 'user_count', 'label' => __('permission::ui.access_manager.table.users')],
            ['key' => 'actions', 'label' => __('ui::common.actions'), 'class' => 'w-1 text-right'],
        ];
    }

    /**
     * Apply query filters.
     */
    protected function applyScoping(Builder $query): Builder
    {
        return $query->withCount(['permissions', 'users']);
    }

    /**
     * Transform record for table.
     */
    protected function mapRecord(mixed $record): array
    {
        return [
            'permission_count' => $record->permissions_count,
            'user_count' => $record->users_count,
            'manageable' => $this->canManageRole($record->name),
        ];
    }

    /**
     * Render the view.
     */
    public function render(): View
    {
        $data = $this->normalizeResults();

        return view('permission::livewire.access-manager', $data)
            ->layout('ui::components.layouts.dashboard', [
                'title' => $this->title,
            ]);
    }
}
