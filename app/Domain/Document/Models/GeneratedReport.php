
declare(strict_types=1);

namespace App\Domain\Document\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Represents a generated report file with metadata.
 *
 * S2 - Sustain: Single source of truth for report generation history.
 */
#[Fillable(['user_id', 'report_type', 'file_path', 'file_size', 'status', 'filters', 'error_message', 'generated_at'])]
class GeneratedReport extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'filters' => 'array',
        'file_size' => 'integer',
        'generated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }
}
