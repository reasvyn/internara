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
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Media\Concerns\InteractsWithMedia;
use Modules\Permission\Enums\Role;
use Modules\Profile\Models\Concerns\HasProfileRelation;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\Status\Concerns\HasStatus;
use Modules\User\Database\Factories\UserFactory;
use Modules\User\Support\UsernameGenerator;
use Spatie\MediaLibrary\HasMedia;
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
    use InteractsWithActivityLog;
    use InteractsWithMedia;
    use MustVerifyEmailTrait;
    use Notifiable;

    /**
     * The name of the activity log for this model.
     */
    protected string $activityLogName = 'profile';

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
        return true;
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
        $this->addMediaCollection(self::COLLECTION_AVATAR)->singleFile();
    }

    /**
     * The URL of the user's avatar.
     */
    public ?string $avatarUrl {
        get => $this->getFirstMediaUrl(self::COLLECTION_AVATAR);
    }

    /**
     * Set the user's avatar.
     */
    public function setAvatar(
        string|UploadedFile $file,
        string $collectionName = self::COLLECTION_AVATAR,
    ): bool {
        return $this->setMedia($file, $collectionName);
    }

    /**
     * Scope a query to only include users with the 'super-admin' role.
     */
    public function scopeSuperAdmin(Builder $query): Builder
    {
        return $query->role(Role::SUPER_ADMIN->value);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
