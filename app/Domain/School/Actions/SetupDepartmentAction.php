
declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\School\Models\Department;
use Illuminate\Support\Facades\DB;

/**
 * Setup a Department during initial installation.
 */
class SetupDepartmentAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @param array{name: string, description: ?string, school_id: string} $data
     */
    public function execute(array $data): Department
    {
        return DB::transaction(function () use ($data) {
            $department = Department::create($data);

            $this->logAudit->execute(
                action: 'department_setup_completed',
                subjectType: Department::class,
                subjectId: $department->id,
                payload: $data,
                module: 'Setup',
            );

            return $department;
        });
    }
}
