declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Concerns\HasUuid;
use App\Enums\RequirementType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Defines what a student needs to submit for an internship program.
 */
#[Fillable(['name', 'description', 'type', 'is_mandatory', 'is_active'])]
class Requirement extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'type' => RequirementType::class,
        'is_mandatory' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function submissions(): HasMany
    {
        return $this->hasMany(RequirementSubmission::class, 'requirement_id');
    }

    public function supportsFileUpload(): bool
    {
        return $this->type?->supportsFileUpload() ?? false;
    }
}
