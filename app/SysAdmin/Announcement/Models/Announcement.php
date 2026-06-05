<?php

declare(strict_types=1);

namespace App\SysAdmin\Announcement\Models;

use App\Core\Models\BaseModel;
use App\SysAdmin\Announcement\Enums\AnnouncementStatus;
use App\User\Models\User;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['title', 'message', 'type', 'status', 'scheduled_at', 'link', 'target_roles', 'created_by'])]
class Announcement extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'target_roles' => 'array',
            'status' => AnnouncementStatus::class,
            'scheduled_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', AnnouncementStatus::PUBLISHED);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', AnnouncementStatus::DRAFT);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', AnnouncementStatus::SCHEDULED);
    }

    public function scopePendingPublish(Builder $query): Builder
    {
        return $query->where('status', AnnouncementStatus::SCHEDULED)
            ->where('scheduled_at', '<=', now());
    }

    public function isPublished(): bool
    {
        return $this->status === AnnouncementStatus::PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === AnnouncementStatus::DRAFT;
    }

    public function isScheduled(): bool
    {
        return $this->status === AnnouncementStatus::SCHEDULED;
    }

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }
}
