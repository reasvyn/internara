<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Guidance\Aggregates\Mentee\Models\Mentee;
use App\Domain\Enrollment\Enums\AccountApplicationStatus;
use App\Domain\Enrollment\Models\AccountApplication;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\User\Aggregates\Profile\Models\Profile;
use App\Domain\User\Models\User;

final class ApproveAccountApplicationAction extends BaseAction
{
    public function execute(string $applicationId, User $admin): Registration
    {
        $application = AccountApplication::findOrFail($applicationId);

        if ($application->status !== AccountApplicationStatus::PENDING) {
            throw new RejectedException('Application is not in pending status.');
        }

        return $this->transaction(function () use ($application, $admin) {
            $application->update([
                'status' => 'approved',
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ]);

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
                'national_id_number' => $application->national_id_number,
                'student_id_number' => $application->student_id_number,
                'school_id' => $application->school_id,
                'department_id' => $application->department_id,
            ]);

            $mentee = Mentee::create([
                'user_id' => $user->id,
            ]);

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

            $this->log('account_application_approved', $application, [
                'user_id' => $user->id,
                'registration_id' => $registration->id,
            ]);

            return $registration;
        });
    }
}
