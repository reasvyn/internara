declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\ModelStatus\HasStatuses;

/**
 * Represents the student's submission for a requirement.
 */
#[Fillable(['registration_id', 'requirement_id', 'value', 'notes', 'verified_at', 'verified_by'])]
class RequirementSubmission extends Model implements HasMedia
{
    use HasFactory, HasStatuses, HasUuid, InteractsWithMedia;

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(Requirement::class, 'requirement_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Define media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document')->singleFile();
    }
}
