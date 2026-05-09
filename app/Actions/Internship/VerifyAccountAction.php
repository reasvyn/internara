<?php

declare(strict_types=1);

namespace App\Actions\Internship;

use App\Actions\Core\LogAuditAction;
use App\Models\AccountApplication;
use App\Models\Mentee;
use App\Models\Profile;
use App\Models\Registration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VerifyAccountAction
{
    public function __construct(
        protected readonly LogAuditAction $logAuditAction,
        protected readonly DirectPlacementAction $directPlacementAction,
    ) {}

    public function approve(string $applicationId, User $admin): Registration
    {
        $application = AccountApplication::findOrFail($applicationId);

        if ($application->status !== 'pending') {
            throw new RuntimeException('Application is not in pending status.');
        }

        return DB::transaction(function () use ($application, $admin) {
            $application->update([
                'status' => 'approved',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

            /** @var User $user */
            $user = User::create([
                'name' => $application->name,
                'email' => $application->email,
                'username' => $application->email,
                'password' => bcrypt(str()->random(32)),
                'setup_required' => true,
            ]);

            $user->assignRole('student');

            Profile::create([
                'user_id' => $user->id,
                'phone' => $application->phone,
                'address' => $application->address,
                'national_identifier' => $application->national_identifier,
                'registration_number' => $application->registration_number,
                'school_id' => $application->school_id,
                'department_id' => $application->department_id,
            ]);

            /** @var Mentee $mentee */
            $mentee = Mentee::create([
                'user_id' => $user->id,
            ]);

            /** @var Registration $registration */
            $registration = Registration::create([
                'mentee_id' => $mentee->id,
                'internship_id' => $application->internship_id,
                'placement_id' => $application->placement_id,
                'academic_year' => $application->academic_year,
                'proposed_company_name' => $application->proposed_company_name,
                'proposed_company_address' => $application->proposed_company_address,
                'start_date' => $application->placement?->internship->start_date ?? $application->internship->start_date,
                'end_date' => $application->placement?->internship->end_date ?? $application->internship->end_date,
            ]);

            $registration->setStatus('active', 'Account application approved by administrator.');

            $this->logAuditAction->execute(
                action: 'account_application_approved',
                subjectType: AccountApplication::class,
                subjectId: $application->id,
                payload: [
                    'user_id' => $user->id,
                    'registration_id' => $registration->id,
                ],
                module: 'Internship',
            );

            return $registration;
        });
    }

    public function reject(string $applicationId, User $admin, string $reason): void
    {
        $application = AccountApplication::findOrFail($applicationId);

        if ($application->status !== 'pending') {
            throw new RuntimeException('Application is not in pending status.');
        }

        $application->update([
            'status' => 'rejected',
            'processed_by' => $admin->id,
            'processed_at' => now(),
            'rejection_reason' => $reason,
        ]);

        $this->logAuditAction->execute(
            action: 'account_application_rejected',
            subjectType: AccountApplication::class,
            subjectId: $application->id,
            payload: ['reason' => $reason],
            module: 'Internship',
        );
    }
}
