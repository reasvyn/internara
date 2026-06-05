<?php

declare(strict_types=1);

namespace App\Core\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for module entities.
 *
 * Entities are immutable business rule objects. Framework dependencies
 * (Eloquent, Carbon) are allowed when practical.
 *
 * @template TModel of Model
 */
abstract readonly class BaseEntity
{
    /**
     * @param TModel $model
     */
    abstract public static function fromModel(Model $model): static;
}
