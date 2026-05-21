<?php

declare(strict_types=1);

namespace App\Domain\Admin\Models;

use App\Domain\Core\Models\BaseModel;
use App\Domain\User\Models\User;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'message', 'type', 'link', 'target_roles', 'created_by'])]
class Announcement extends BaseModel
{
    use HasFactory;

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

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}
