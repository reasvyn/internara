declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\Internship\Models\Internship;
use Illuminate\Support\Facades\DB;

class DeleteInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(Internship $internship): void
    {
        DB::transaction(function () use ($internship) {
            $internshipId = $internship->id;
            $internshipName = $internship->name;

            $internship->delete();

            $this->logAudit->execute(
                action: 'internship_deleted',
                subjectType: Internship::class,
                subjectId: $internshipId,
                payload: ['name' => $internshipName],
                module: 'Internship',
            );
        });
    }
}
