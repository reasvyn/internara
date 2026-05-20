<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contract for models/components that support filter functionality.
 */
interface Filterable
{
    public function applyFilters(Builder $query): Builder;
}
