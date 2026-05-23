<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire;

use App\Domain\Auth\Actions\UpdateRolePermissionsAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessManager extends Component
{
    public bool $roleModal = false;

    public ?Role $selectedRole = null;

    public array $selectedPermissions = [];

    public function roles(): Collection
    {
        return Role::withCount(['permissions', 'users'])->get();
    }

    public function permissions(): Collection
    {
        return Permission::all();
    }

    public function editRolePermissions(Role $role): void
    {
        $this->selectedRole = $role;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->roleModal = true;
    }

    public function savePermissions(UpdateRolePermissionsAction $updateAction): void
    {
        if (! $this->selectedRole) {
            return;
        }

        $updateAction->execute($this->selectedRole, $this->selectedPermissions);

        flash()->success(__('auth.permissions_updated'));
        $this->roleModal = false;
    }

    public function render(): View
    {
        return view('auth.access-manager', [
            'roles' => $this->roles(),
            'permissions' => $this->permissions(),
        ]);
    }
}
