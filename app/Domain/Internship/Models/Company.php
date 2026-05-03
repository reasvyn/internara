declare(strict_types=1);

namespace App\Domain\Internship\Models;

use App\Domain\Core\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a company/industry partner.
 */
#[Fillable(['name', 'address', 'phone', 'email', 'website', 'description', 'industry_sector'])]
class Company extends Model
{
    use HasFactory, HasUuid;

    public function placements(): HasMany
    {
        return $this->hasMany(Placement::class, 'company_id');
    }
}
