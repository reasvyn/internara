<?php

declare(strict_types=1);

namespace Modules\Permission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Permission\Database\Factories\RoleFactory;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\Permission\Models\Role as BaseRole;

class Role extends BaseRole
{
    use HasFactory;
    use HasUuid;

    /**
     * Indicates if the IDs are auto-incrementing.
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

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'guard_name', 'description', 'module'];

    /**
     * Determine if the model should use UUIDs.
     */
    protected function usesUuid(): bool
    {
        return true;
    }

    protected static function newFactory(): RoleFactory
    {
        return RoleFactory::new();
    }
}
