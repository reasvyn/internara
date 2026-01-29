<?php

declare(strict_types=1);

namespace Modules\Log\Models;

use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\Activitylog\Models\Activity as SpatieActivity;

/**
 * Class Activity
 *
 * Custom Activity model to support UUID identity.
 */
class Activity extends SpatieActivity
{
    use HasUuid;

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';
}
