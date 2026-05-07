<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model
{
    use HasUuids;

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Resolve the domain entity for business rule evaluation.
     *
     * Override in subclass to return the matching Entity instance.
     */
    public function entity(): ?object
    {
        return null;
    }
}
