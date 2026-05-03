declare(strict_types=1);

namespace App\Domain\Assignment\Models;

use App\Domain\Core\Concerns\HasUuid;
use Database\Factories\AssignmentTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Assignment type template for defining task categories.
 *
 * S2 - Sustain: Centralized task type management.
 */
#[Fillable(['name', 'slug', 'group', 'description'])]
class AssignmentType extends Model
{
    use HasFactory, HasUuid;

    /**
     * Get assignments of this type.
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class, 'assignment_type_id');
    }

    /**
     * Create a new factory instance.
     */
    protected static function newFactory(): AssignmentTypeFactory
    {
        return AssignmentTypeFactory::new();
    }
}
