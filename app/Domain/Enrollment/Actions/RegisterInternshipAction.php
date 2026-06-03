<?php

declare(strict_types=1);

namespace App\Domain\Enrollment\Actions;

use App\Domain\Core\Actions\BaseAction;
use App\Domain\Core\Exceptions\RejectedException;
use App\Domain\Program\Aggregates\Internship\Models\Internship;
use App\Domain\Program\Notifications\RegistrationNotification;
use App\Domain\Guidance\Aggregates\Mentee\Models\Mentee;
use App\Domain\Enrollment\Models\Registration;
use App\Domain\User\Models\User;

final class RegisterInternshipAction extends BaseAction
{
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
            throw new RejectedException('Student already has an active or pending internship registration.');
        }

        return $this->transaction(function () use ($student, $data) {
            $mentee = Mentee::create(['user_id' => $student->id]);

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
            $student->notify(new RegistrationNotification($internship->name, 'pending'));

            $this->log('internship_registered', $registration, $data);

            return $registration;
        });
    }
}
