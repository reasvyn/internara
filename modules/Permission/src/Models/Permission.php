<?php

declare(strict_types=1);

namespace Modules\Permission\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Permission\Database\Factories\PermissionFactory;
use Modules\Shared\Models\Concerns\HasUuid;
use Spatie\Permission\Models\Permission as BasePermission;

class Permission extends BasePermission
{
    use HasFactory;
    use HasUuid;

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
        return config('permission.model_key_type') === 'uuid';
    }

    protected static function newFactory(): PermissionFactory
    {
        return PermissionFactory::new();
    }
}
