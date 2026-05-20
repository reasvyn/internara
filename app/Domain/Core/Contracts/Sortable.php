<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contract for models/components that support sort functionality.
 */
interface Sortable
{
    public function applySorting(Builder $query): Builder;
}
