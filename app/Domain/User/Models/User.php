<?php

declare(strict_types=1);

namespace App\Domain\User\Models;

use App\Domain\Auth\Entities\Apprentice;
use App\Domain\Guidance\Models\HandbookAcknowledgement;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Mentor\Models\Mentor;
use App\Domain\Registration\Models\Registration;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\ModelStatus\HasStatuses;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'username', 'password', 'setup_required', 'locked_at', 'locked_reason'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements HasMedia
{
    use HasFactory, HasRoles, HasStatuses, HasUuids, InteractsWithMedia, Notifiable;

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'locked_at' => 'datetime',
            'password' => 'hashed',
            'setup_required' => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function mentees(): HasMany
    {
        return $this->hasMany(Mentee::class);
    }

    public function mentors(): HasMany
    {
        return $this->hasMany(Mentor::class);
    }

    public function registrations(): HasManyThrough
    {
        return $this->hasManyThrough(Registration::class, Mentee::class, 'user_id', 'mentee_id');
    }

    public function handbookAcknowledgements(): HasMany
    {
        return $this->hasMany(HandbookAcknowledgement::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->format('webp')
            ->nonQueued();
    }

    public function initials(): string
    {
        $words = explode(' ', trim($this->name));

        if (count($words) >= 2) {
            return strtoupper(
                substr($words[0], 0, 1).substr(end($words), 0, 1)
            );
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    public function receivesBroadcastNotificationsOn(): string
    {
        return 'App.Domain.User.Models.User.'.$this->id;
    }

    public function asApprentice(): Apprentice
    {
        return Apprentice::fromModel($this);
    }

    public function scopeLocked(Builder $query): Builder
    {
        return $query->whereNotNull('locked_at');
    }

    public function scopeUnlocked(Builder $query): Builder
    {
        return $query->whereNull('locked_at');
    }

    public function getActiveRegistration(): ?Registration
    {
        return $this->registrations()
            ->whereHas('statuses', fn ($q) => $q->where('name', 'active'))
            ->first();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->unlocked()->where('setup_required', false);
    }

    public function scopeRoleType(Builder $query, string $role): Builder
    {
        return $query->whereHas('roles', fn ($q) => $q->where('name', $role));
    }
}
