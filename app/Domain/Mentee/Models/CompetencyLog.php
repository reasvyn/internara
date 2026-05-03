declare(strict_types=1);

namespace App\Domain\Mentee\Models;

use App\Domain\Core\Concerns\HasUuid;
use Database\Factories\CompetencyLogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Student competency assessment log.
 *
 * S2 - Sustain: Tracks competency progress over time.
 */
#[Fillable(['registration_id', 'competency_id', 'evaluator_id', 'score', 'notes'])]
class CompetencyLog extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'score' => 'float',
    ];

    /**
     * Get the internship registration.
     */
    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }

    /**
     * Get the competency.
     */
    public function competency(): BelongsTo
    {
        return $this->belongsTo(Competency::class);
    }

    /**
     * Get the evaluator.
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): CompetencyLogFactory
    {
        return CompetencyLogFactory::new();
    }
}
