<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\Registration\Actions;

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationStatus;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ProvisionCohortRegistrationAction extends BaseCommandAction
{
    /**
     * Provisions preparation registration records for a cohort of eligible students.
     * Skips students who already have an active, pending, or preparation registration.
     *
     * @param iterable<User>|Collection<int, User> $students
     *
     * @return Collection<int, Registration>
     */
    public function execute(Internship $internship, iterable $students): Collection
    {
        return $this->transaction(function () use ($internship, $students) {
            $created = new Collection;

            foreach ($students as $student) {
                $hasActiveRegistration = Registration::where('student_id', $student->id)
                    ->get()
                    ->filter(fn (Registration $reg) => in_array($reg->status, [
                        RegistrationStatus::PREPARATION->value,
                        RegistrationStatus::PENDING->value,
                        RegistrationStatus::CONFIRMED->value,
                        RegistrationStatus::ACTIVE->value,
                        RegistrationStatus::COMPLETED->value,
                    ], true))
                    ->isNotEmpty();

                if ($hasActiveRegistration) {
                    continue;
                }

                $registration = Registration::create([
                    'student_id' => $student->id,
                    'internship_id' => $internship->id,
                    'status' => RegistrationStatus::PREPARATION->value,
                    'start_date' => $internship->start_date,
                    'end_date' => $internship->end_date,
                ]);

                $created->push($registration);
            }

            return $created;
        });
    }
}
