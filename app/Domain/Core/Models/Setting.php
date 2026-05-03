declare(strict_types=1);

namespace App\Domain\Core\Models;

use App\Domain\Core\Casts\SettingValueCast;
use App\Domain\Core\Concerns\HasUuid;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * System setting model with typed value storage.
 *
 * S2 - Sustain: Centralized system configuration with proper type handling.
 */
#[Fillable(['key', 'value', 'type', 'description', 'group'])]
class Setting extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'value' => SettingValueCast::class,
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }

    /**
     * Scope a query to only include settings belonging to a given group.
     */
    public function scopeGroup(Builder $query, string $name): void
    {
        $query->where('group', $name);
    }
}
