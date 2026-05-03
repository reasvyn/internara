declare(strict_types=1);

namespace App\Domain\Logbook\Actions;

use App\Domain\Core\Actions\LogAuditAction;
use App\Domain\User\Models\User;
use App\Domain\Logbook\Models\LogbookEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SubmitLogbookEntryAction
{
    public function __construct(protected readonly LogAuditAction $logAudit) {}

    public function execute(User $user, array $data): LogbookEntry
    {
        return DB::transaction(function () use ($user, $data) {
            $date = Carbon::now()->toDateString();

            // Find active registration (using Spatie HasStatuses)
            $registration = $user
                ->registrations()
                ->get()
                ->first(fn ($reg) => $reg->hasStatus('active'));

            if (! $registration) {
                throw new RuntimeException('No active internship registration found.');
            }

            // Check if a submitted journal already exists for today
            $existing = LogbookEntry::where('user_id', $user->id)
                ->whereDate('date', $date)
                ->where('status', 'submitted')
                ->first();

            if ($existing) {
                throw new RuntimeException('Journal entry for today has already been submitted.');
            }

            // Update existing draft or create new
            $journal = LogbookEntry::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'date' => $date,
                ],
                [
                    'registration_id' => $registration->id,
                    'content' => $data['content'],
                    'learning_outcomes' => $data['learning_outcomes'] ?? null,
                    'status' => 'submitted',
                ],
            );

            $this->logAudit->execute(
                action: 'journal_submitted',
                subjectType: LogbookEntry::class,
                subjectId: $journal->id,
                payload: ['date' => $journal->date],
                module: 'Journal',
            );

            return $journal;
        });
    }
}
