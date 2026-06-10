<?php

declare(strict_types=1);

namespace App\Enrollment\Registration\Actions;

use App\Core\Actions\BaseAction;
use App\Core\Exceptions\RejectedException;
use App\Enrollment\Registration\Data\RegistrationData;
use App\Enrollment\Registration\Events\StudentRegistered;
use App\Enrollment\Registration\Models\Registration;
use App\Guidance\Mentee\Models\Mentee;
use App\Program\Internship\Models\Internship;
use App\Program\Notifications\RegistrationNotification;
use App\User\Models\User;

final class RegisterInternshipAction extends BaseAction
{
    public function execute(User $student, RegistrationData $data): Registration
    {
        $hasExisting = Registration::whereHas(
            'mentee',
            fn ($q) => $q->where('user_id', $student->id),
        )
            ->get()
            ->filter(fn ($reg) => $reg->hasStatus('active') || $reg->hasStatus('pending'))
            ->isNotEmpty();

        if ($hasExisting) {
            throw new RejectedException(__('registration.already_registered'));
        }

        return $this->transaction(function () use ($student, $data) {
            $mentee = Mentee::create(['user_id' => $student->id]);

            $registration = Registration::create([
                'mentee_id' => $mentee->id,
                'internship_id' => $data->internshipId,
                'placement_id' => $data->placementId,
                'academic_year' => $data->academicYear,
                'start_date' => $data->startDate,
                'end_date' => $data->endDate,
                'proposed_company_name' => $data->proposedCompanyName,
                'proposed_company_address' => $data->proposedCompanyAddress,
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
