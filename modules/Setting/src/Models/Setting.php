<?php

declare(strict_types=1);

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Core\Models\Concerns\HandlesAuditLog;
use Modules\Setting\Database\Factories\SettingFactory;

class Setting extends Model
{
    use HandlesAuditLog, HasFactory;

    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'key';

    /**
     * The "type" of the primary key ID.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = ['key', 'value', 'type', 'description', 'group'];

    /**
     * The model's attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => \Modules\Setting\Casts\SettingValueCast::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    /**
     * Scope a query to only include settings belonging to a given group.
     */
    public function scopeGroup(\Illuminate\Database\Eloquent\Builder $query, string $name): void
    {
        $query->where('group', $name);
    }
}
