<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\Internship;
use App\Models\Mentee;
use App\Models\Registration;
use App\Models\User;
use App\Notifications\Internship\RegistrationNotification;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RegisterInternshipAction
{
    public function __construct(protected readonly LogAuditAction $logAuditAction) {}

    /**
     * @throws RuntimeException if student already has an active or pending registration
     */
    public function execute(User $student, array $data): Registration
    {
        $hasExisting = Registration::whereHas(
            'mentee',
            fn ($q) => $q->where('user_id', $student->id),
        )
            ->get()
            ->filter(fn ($reg) => $reg->hasStatus('active') || $reg->hasStatus('pending'))
            ->isNotEmpty();

        if ($hasExisting) {
            throw new RuntimeException(
                'Student already has an active or pending internship registration.',
            );
        }

        return DB::transaction(function () use ($student, $data) {
            /** @var Mentee $mentee */
            $mentee = Mentee::create([
                'user_id' => $student->id,
            ]);

            /** @var Registration $registration */
            $registration = Registration::create([
                'mentee_id' => $mentee->id,
                'internship_id' => $data['internship_id'],
                'placement_id' => filled($data['placement_id'] ?? null) ? $data['placement_id'] : null,
                'academic_year' => $data['academic_year'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'proposed_company_name' => $data['proposed_company_name'] ?? null,
                'proposed_company_address' => $data['proposed_company_address'] ?? null,
            ]);

            $registration->setStatus('pending', 'Initial registration submitted by student.');

            $internship = Internship::find($data['internship_id']);
            $student->notify(
                new RegistrationNotification(
                    $internship->name,
                    'pending',
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
