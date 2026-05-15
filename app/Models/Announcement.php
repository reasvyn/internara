<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'message',
        'type',
        'link',
        'target_roles',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
