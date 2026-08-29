<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\AccountApplication\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\Enrollment\Domain\AccountApplication\Events\AccountApplicationApproved;
use App\Modules\Enrollment\Domain\AccountApplication\Models\AccountApplication;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\User\Models\User;
use App\Modules\User\Domain\Profile\Models\Profile;

final class ApproveAccountApplicationAction extends BaseCommandAction
{
    public function execute(string $applicationId, User $admin): Registration
    {
        $application = AccountApplication::findOrFail($applicationId);

        if ($application->status !== AccountApplicationStatus::PENDING) {
            throw new RejectedException(__('registration.application_not_pending'));
        }

        return $this->transaction(function () use ($application, $admin) {
            $application->update([
                'status' => AccountApplicationStatus::APPROVED->value,
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

            $formData = $application->form_data;

            if (empty($formData['internship_id'])) {
                throw new RejectedException(
                    __('registration.validation.missing_internship'),
                );
            }

            Profile::create([
                'user_id' => $user->id,
                'phone' => $formData['phone'] ?? null,
                'address' => $formData['address'] ?? null,
                'id_number' => $application->student_id_number,
                'department_id' => $application->department_id,
            ]);

            $registration = Registration::create([
                'student_id' => $user->id,
                'internship_id' => $formData['internship_id'] ?? null,
                'placement_id' => $formData['placement_id'] ?? null,
                'proposed_company_details' => [
                    'company_name' => $formData['proposed_company_name'] ?? null,
                    'address' => $formData['proposed_company_address'] ?? null,
                ],
                'start_date' => $formData['start_date'] ?? null,
                'end_date' => $formData['end_date'] ?? null,
            ]);

            $registration->setStatus('active', 'Account application approved by administrator.');

            $this->log('account_application_approved', $application, [
                'user_id' => $user->id,
                'registration_id' => $registration->id,
            ]);

            event(new AccountApplicationApproved($application));

            return $registration;
        });
    }
}
