<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\Permission\UpdateRolePermissionsAction;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Mary\Traits\Toast;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessManager extends Component
{
    use Toast;

    public bool $roleModal = false;

    public ?Role $selectedRole = null;

    public array $selectedPermissions = [];

    /**
     * Get all roles.
     */
    public function roles(): Collection
    {
        return Role::withCount(['permissions', 'users'])->get();
    }

    /**
     * Get all permissions grouped by their module/category.
     */
    public function permissions(): Collection
    {
        return Permission::all();
    }

    /**
     * Open modal to edit permissions for a role.
     */
    public function editRolePermissions(Role $role): void
    {
        $this->selectedRole = $role;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->roleModal = true;
    }

    /**
     * Save role permissions.
     */
    public function savePermissions(UpdateRolePermissionsAction $updateAction): void
    {
        if (! $this->selectedRole) {
            return;
        }

        $updateAction->execute($this->selectedRole, $this->selectedPermissions);

        $this->success('Permissions updated successfully.');
        $this->roleModal = false;
    }

    public function render()
    {
        return view('livewire.admin.access-manager', [
            'roles' => $this->roles(),
            'permissions' => $this->permissions(),
        ]);
    }
}
