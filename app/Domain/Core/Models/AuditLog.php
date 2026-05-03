declare(strict_types=1);

namespace App\Domain\Core\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'subject_id', 'subject_type', 'action', 'payload', 'ip_address', 'user_agent', 'module'])]
class AuditLog extends Model
{
    use HasUuid;

    protected $casts = [
        'payload' => 'array',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
