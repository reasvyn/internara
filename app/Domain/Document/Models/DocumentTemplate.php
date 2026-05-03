
declare(strict_types=1);

namespace App\Domain\Document\Models;

use App\Domain\Core\Concerns\HasUuid;
use App\Enums\DocumentCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Templates for generating formal letters and documents.
 */
#[Fillable(['name', 'slug', 'description', 'content', 'is_active', 'category'])]
class DocumentTemplate extends Model
{
    use HasFactory, HasUuid;

    protected $casts = [
        'category' => DocumentCategory::class,
        'is_active' => 'boolean',
    ];

    public function documents(): HasMany
    {
        return $this->hasMany(OfficialDocument::class, 'template_id');
    }
}
