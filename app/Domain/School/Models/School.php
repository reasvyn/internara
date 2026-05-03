declare(strict_types=1);

namespace App\Domain\School\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['institutional_code', 'name', 'address', 'email', 'phone', 'fax', 'principal_name', 'website'])]
#[Appends(['logo_url'])]
class School extends Model implements HasMedia
{
    use HasFactory, HasUuid, InteractsWithMedia;

    public const COLLECTION_LOGO = 'logo';

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function internships(): HasMany
    {
        return $this->hasManyThrough(Internship::class, Department::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::COLLECTION_LOGO)->singleFile();
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl(self::COLLECTION_LOGO) ?? null;
    }

    public function setLogo(UploadedFile|string $file): bool
    {
        $this->clearMediaCollection(self::COLLECTION_LOGO);

        return $this->addMedia($file)->toMediaCollection(self::COLLECTION_LOGO) !== null;
    }

    public function canBeCreated(): bool
    {
        return config('school.single_record', true) ? $this->newQuery()->doesntExist() : true;
    }
}
