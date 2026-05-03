
declare(strict_types=1);

namespace App\Domain\School\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\School\Models\School;
use App\Exceptions\SetupException;
use Illuminate\Support\Facades\DB;

/**
 * Setup the School profile during initial installation.
 */
class SetupSchoolAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * @param array{name: string, institutional_code: string, address: string, email: ?string, phone: ?string, website: ?string, principal_name: ?string} $data
     *
     * @throws SetupException when school already exists
     */
    public function execute(array $data): School
    {
        if (! (new School)->canBeCreated()) {
            throw SetupException::schoolAlreadyExists();
        }

        return DB::transaction(function () use ($data) {
            $school = School::create($data);

            $this->logAudit->execute(
                action: 'school_setup_completed',
                subjectType: School::class,
                subjectId: $school->id,
                payload: [
                    'name' => $data['name'],
                    'code' => $data['institutional_code'],
                ],
                module: 'Setup',
            );

            return $school;
        });
    }
}
