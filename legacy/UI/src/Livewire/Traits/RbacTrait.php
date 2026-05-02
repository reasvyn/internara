<?php

declare(strict_types=1);

namespace Modules\UI\Livewire\Traits;

use Illuminate\Support\Facades\Gate;
use Modules\Permission\Enums\Permission;

/**
 * Trait RbacTrait
 *
 * Provides standardized Role-Based Access Control integration for Livewire components.
 * Supports automatic mapping of common actions (view, create, update, delete) to
 * specific permissions or model-based policies.
 */
trait RbacTrait
{
    /**
     * Resolve permission to string value (handles both enum and string).
     */
    protected function resolvePermissionValue(mixed $permission): ?string
    {
        if ($permission === null || $permission === '') {
            return null;
        }

        if ($permission instanceof Permission) {
            return $permission->value;
        }

        return (string) $permission;
    }

    /**
     * Determine if the user has permission to perform an action.
     */
    public function can(string $action, mixed $target = null): bool
    {
        $isSetupAuthorized = (bool) session('setup_authorized');
        if ($isSetupAuthorized) {
            return true;
        }

        $user = auth()->user();
        if (! $user) {
            return false;
        }

        $target = $target ?: ($this->modelClass ?? null);

        $permissionValue = $this->resolvePermissionValue(
            match ($action) {
                'view' => $this->viewPermission ?? null,
                'create' => $this->createPermission ?? null,
                'update' => $this->updatePermission ?? null,
                'delete' => $this->deletePermission ?? null,
                default => null,
            }
        );

        return match ($action) {
            'view', 'create' => $permissionValue
                ? $user->can($permissionValue)
                : true,
            'update', 'delete' => $permissionValue
                ? $user->can($permissionValue)
                : ($target ? $user->can($action, $target) : true),
            default => false,
        };
    }

    /**
     * Authorize the user for a specific action using RBAC permissions.
     *
     * Uses the component's permission properties to authorize.
     *
     * @param string $action The action (view, create, update, delete)
     * @param mixed $target Optional target model for policy-based authorization
     */
    public function rbacAuthorize(string $action, mixed $target = null): void
    {
        $isSetupAuthorized = (bool) session('setup_authorized');
        if ($isSetupAuthorized) {
            return;
        }

        $target = $target ?: ($this->modelClass ?? null);

        $permissionValue = $this->resolvePermissionValue(
            match ($action) {
                'view' => $this->viewPermission ?? null,
                'create' => $this->createPermission ?? null,
                'update' => $this->updatePermission ?? null,
                'delete' => $this->deletePermission ?? null,
                default => null,
            }
        );

        if ($permissionValue) {
            Gate::authorize($permissionValue);
        } elseif ($target) {
            Gate::authorize($action, $target);
        }
    }
}
