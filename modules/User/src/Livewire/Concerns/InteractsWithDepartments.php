<?php

declare(strict_types=1);

namespace Modules\User\Livewire\Concerns;

use Livewire\Attributes\Computed;
use Modules\Department\Services\Contracts\DepartmentService;

/**
 * Trait InteractsWithDepartments
 * 
 * Provides access to department data for Livewire components.
 */
trait InteractsWithDepartments
{
    /**
     * Get departments for the select input.
     */
    #[Computed]
    public function departments(): \Illuminate\Support\Collection
    {
        if (interface_exists(DepartmentService::class)) {
            return app(DepartmentService::class)->all([
                'id',
                'name',
            ]);
        }

        return collect();
    }
}
