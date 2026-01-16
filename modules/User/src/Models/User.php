<?php

declare(strict_types=1);

namespace Modules\User\Models;

use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Modules\Profile\Models\Concerns\HasProfileRelation;
use Modules\Shared\Models\Concerns\HasStatus;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\User\Database\Factories\UserFactory;
use Modules\User\Support\UsernameGenerator;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

/**
 * Represents a user in the system.
 */
class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasFactory;
    use HasProfileRelation;
    use HasRoles;
    use HasStatus;
    use HasUuid;
    use InteractsWithMedia;
    use MustVerifyEmailTrait;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = ['name', 'email', 'username', 'password'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = ['password', 'remember_token'];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (empty($user->username)) {
                $user->username = UsernameGenerator::generate();
            }
        });
    }

    /**
     * Determine if the model should use UUIDs.
     */
    protected function usesUuid(): bool
    {
        return config('user.type_id') === 'uuid';
    }

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials.
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn (string $word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * Register the media collections for the user's avatar.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('user_avatar')->singleFile();
    }

    /**
     * Get the URL of the user's avatar.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('user_avatar') ?: null;
    }

    /**
     * Set the user's avatar.
     */
    public function setAvatar(
        string|UploadedFile $file,
        string $collectionName = 'user_avatar',
    ): bool {
        $this->clearMediaCollection($collectionName);

        return (bool) $this->addMedia($file)->toMediaCollection($collectionName);
    }

    /**
     * Scope a query to only include users with the 'super-admin' role.
     */
    public function scopeSuperAdmin(Builder $query): Builder
    {
        return $query->role('super-admin');
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
