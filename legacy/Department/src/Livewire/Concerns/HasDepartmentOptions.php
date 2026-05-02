<?php

declare(strict_types=1);

namespace Modules\Department\Livewire\Concerns;

use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Modules\Department\Services\Contracts\DepartmentService;

trait HasDepartmentOptions
{
    #[Computed]
    public function departments(): Collection
    {
        return app(DepartmentService::class)->all(['id', 'name']);
    }
}
