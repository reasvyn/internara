declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Placement;
use Illuminate\Support\Facades\DB;

class CreatePlacementAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(array $data): Placement
    {
        return DB::transaction(function () use ($data) {
            $data['filled_quota'] = 0;
            $placement = Placement::create($data);

            $this->logAudit->execute(
                action: 'placement_created',
                subjectType: Placement::class,
                subjectId: $placement->id,
                payload: ['name' => $placement->name, 'quota' => $placement->quota],
                module: 'Internship',
            );

            return $placement;
        });
    }
}
