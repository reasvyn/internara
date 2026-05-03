declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Events\Internship\InternshipCreated;
use App\Http\Requests\Internship\CreateInternshipRequest;
use App\Domain\Internship\Models\Internship;
use Illuminate\Support\Facades\DB;

/**
 * Action to create a new internship.
 *
 * S1 - Secure: Receives validated data from Form Request.
 * S2 - Sustain: Single responsibility, clear use case.
 * S3 - Scalable: Can be called from Controllers (API) or Livewire (Web).
 */
class CreateInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    /**
     * Execute the internship creation use case.
     *
     * @param CreateInternshipRequest|array $input Validated data
     */
    public function execute(CreateInternshipRequest|array $input): Internship
    {
        // Handle both Form Request object and array input
        $data = is_array($input) ? $input : $input->validated();

        return DB::transaction(function () use ($data) {
            $internship = Internship::create($data);

            // Option 1: Direct audit logging (simple case)
            $this->logAudit->execute(
                action: 'internship_created',
                subjectType: Internship::class,
                subjectId: $internship->id,
                payload: ['name' => $internship->name],
                module: 'Internship',
            );

            // Option 2: Event-driven side effects (complex case)
            event(new InternshipCreated($internship, auth()->user()));

            return $internship;
        });
    }
}
