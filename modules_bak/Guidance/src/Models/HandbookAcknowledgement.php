<?php

declare(strict_types=1);

namespace Modules\Guidance\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Shared\Models\Concerns\HasUuid;

class HandbookAcknowledgement extends Model
{
    use HasFactory;
    use HasUuid;

    protected $fillable = ['student_id', 'handbook_id', 'acknowledged_at'];

    protected $casts = [
        'acknowledged_at' => 'datetime',
    ];
}
