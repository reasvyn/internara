<?php

declare(strict_types=1);

namespace App\Domain\Core\Entities;

use Illuminate\Database\Eloquent\Model;

/**
 * Base class for domain entities.
 *
 * Entities are pure business rule objects with zero framework dependencies
 * EXCEPT this single `Model` import, which is the sole bridge to the ORM
 * via the `fromModel()` factory method.
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
