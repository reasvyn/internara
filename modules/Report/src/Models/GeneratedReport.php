<?php

declare(strict_types=1);

namespace Modules\Report\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class GeneratedReport extends Model
{
    use HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'provider_identifier',
        'file_path',
        'filters',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
    ];

    /**
     * Get the user who generated the report.
     */
    public function user()
    {
        return app(\Modules\User\Services\Contracts\UserService::class)->defineBelongsTo($this, 'user_id');
    }
}
