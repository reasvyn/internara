<?php

declare(strict_types=1);

namespace Modules\Permission\Livewire;

use Livewire\Component;
use Modules\Permission\Models\Role;

/**
 * Class RoleBadge
 *
 * A shared UI component to display a visual badge for a given role.
 */
class RoleBadge extends Component
{
    /**
     * The role instance or name to display.
     */
    public mixed $role;

    /**
     * Optional size of the badge (xs, sm, md, lg).
     */
    public string $size = 'sm';

    /**
     * Mount the component.
     */
    public function mount(mixed $role, string $size = 'sm'): void
    {
        $this->role = $role;
        $this->size = $size;
    }

    /**
     * Render the component.
     */
    public function render()
    {
        $roleName = $this->role instanceof Role ? $this->role->name : (string) $this->role;
        $color = $this->getRoleColor($roleName);

        return view('permission::livewire.role-badge', [
            'roleName' => $roleName,
            'color' => $color,
        ]);
    }

    /**
     * Get the CSS color class based on the role name.
     */
    protected function getRoleColor(string $name): string
    {
        return match (strtolower($name)) {
            'super-admin' => 'badge-error',
            'admin' => 'badge-primary',
            'teacher' => 'badge-secondary',
            'student' => 'badge-accent',
            default => 'badge-ghost',
        };
    }
}
