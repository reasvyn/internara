declare(strict_types=1);

namespace App\Domain\Internship\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\Internship\Models\Internship;
use App\Domain\Internship\Models\Registration;
use App\Notifications\RegistrationNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * S1 - Secure: Atomic registration with auditing and duplicate prevention.
 * S3 - Scalable: Stateless action.
 */
class RegisterInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * Execute the student internship registration.
     *
     * @throws RuntimeException if student already has an active or pending registration
     */
    public function execute(User $student, array $data): Registration
    {
        $existing = Registration::where('student_id', $student->id)
            ->get()
            ->filter(fn ($reg) => $reg->hasStatus('active') || $reg->hasStatus('pending'))
            ->isNotEmpty();

        if ($existing) {
            throw new RuntimeException(
                'Student already has an active or pending internship registration.',
            );
        }

        return DB::transaction(function () use ($student, $data) {
            /** @var Registration $registration */
            $registration = Registration::create([
                'student_id' => $student->id,
                'internship_id' => $data['internship_id'],
                'placement_id' => $data['placement_id'] ?? null,
                'academic_year' => $data['academic_year'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'proposed_company_name' => $data['proposed_company_name'] ?? null,
                'proposed_company_address' => $data['proposed_company_address'] ?? null,
            ]);

            $registration->setStatus('pending', 'Initial registration submitted by student.');

            // Notify Student
            $internship = Internship::find($data['internship_id']);
            $student->notify(
                new RegistrationNotification(
                    $internship->name,
                    'pending',
                    'Your registration has been submitted and is awaiting review.',
                ),
            );

            $this->logAuditAction->execute(
                action: 'internship_registered',
                subjectType: Registration::class,
                subjectId: $registration->id,
                payload: $data,
                module: 'Internship',
            );

            return $registration;
        });
    }
}
