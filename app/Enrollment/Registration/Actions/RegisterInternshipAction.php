<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Data\RegistrationData;
use App\Enrollment\Registration\Events\StudentRegistered;
use App\Enrollment\Registration\Models\Registration;
use App\Program\Internship\Models\Internship;
use App\Program\Notifications\RegistrationNotification;
use App\User\Models\User;

final class RegisterInternshipAction extends BaseAction
{
    public function execute(User $student, RegistrationData $data): Registration
    {
        $hasExisting = Registration::where('student_id', $student->id)
            ->get()
            ->filter(fn ($reg) => $reg->hasStatus('active') || $reg->hasStatus('pending'))
            ->isNotEmpty();

        if ($hasExisting) {
            throw new RejectedException(__('registration.already_registered'));
        }

        return $this->transaction(function () use ($student, $data) {
            $registration = Registration::create([
                'student_id' => $student->id,
                'internship_id' => $data->internshipId,
                'placement_id' => $data->placementId,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'proposed_company_details' => json_encode([
                    'company_name' => $data->proposedCompanyName,
                    'company_address' => $data->proposedCompanyAddress,
                ]),
            ]);

            $registration->setStatus('pending', 'Initial registration submitted by student.');

            $internship = Internship::find($data->internshipId);
            $student->notify(new RegistrationNotification($internship->name, 'pending'));

            $this->dispatchEvent(new StudentRegistered($registration));

            $this->log('internship_registered', $registration, $data->toArray());

            return $registration;
        });
    }
}
