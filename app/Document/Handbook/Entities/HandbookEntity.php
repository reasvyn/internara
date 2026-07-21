<?php

declare(strict_types=1);

namespace App\Document\Handbook\Entities;

use App\Core\Entities\BaseEntity;
use App\Document\Handbook\Enums\HandbookAudience;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Models\Activity;

final readonly class HandbookEntity extends BaseEntity
{
    public function __construct(
        private string $id,
        private string $title,
        private int $version,
        private bool $isActive,
        private HandbookAudience $audience,
        private ?string $description,
        private bool $hasFile,
        private ?string $createdAt,
    ) {}

    public static function fromModel(Model $model): static
    {
        $metadata = $model->metadata ?? [];
        $audience = HandbookAudience::tryFrom($metadata['target_audience'] ?? 'all')
            ?? HandbookAudience::ALL;

        return new self(
            id: $model->id,
            title: $model->title,
            version: $model->version ?? 1,
            isActive: $model->is_active ?? false,
            audience: $audience,
            description: $metadata['description'] ?? null,
            hasFile: $model->relationLoaded('media') ? $model->media->isNotEmpty() : false,
            createdAt: $model->created_at?->toIso8601String(),
        );
    }

    public function isTargetedAt(?User $user): bool
    {
        if ($this->audience === HandbookAudience::ALL) {
            return true;
        }

        if ($user === null) {
            return false;
        }

        return $user->hasRole($this->audience->value);
    }

    public function isNewerThan(?Activity $lastAcknowledgment): bool
    {
        if ($lastAcknowledgment === null) {
            return true;
        }

        $acknowledgedVersion = (int) ($lastAcknowledgment->properties['version'] ?? 0);

        return $this->version > $acknowledgedVersion;
    }

    public function isAvailable(): bool
    {
        return $this->isActive && $this->hasFile;
    }

    public function canBeDeleted(): bool
    {
        return true;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function audience(): HandbookAudience
    {
        return $this->audience;
    }

    public function description(): ?string
    {
        return $this->description;
    }
}
