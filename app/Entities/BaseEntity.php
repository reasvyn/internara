<?php

declare(strict_types=1);

namespace App\Entities;

use Illuminate\Database\Eloquent\Model;

abstract readonly class BaseEntity
{
    /**
     * Factory method to create entity from an Eloquent model.
     *
     * This is the only bridge between ORM and domain logic.
     * Business rule methods remain pure and testable without a database.
     */
    abstract public static function fromModel(Model $model): static;
}
