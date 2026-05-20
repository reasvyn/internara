<?php

declare(strict_types=1);

namespace App\Domain\Core\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Contract for models/components that support search functionality.
 */
interface Searchable
{
    public function applySearch(Builder $query): Builder;
}
