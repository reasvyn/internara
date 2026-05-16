<?php

declare(strict_types=1);

namespace App\Rules\Internship;

use App\Models\Internship;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class OpenForRegistration implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $internship = Internship::find($value);

        if ($internship === null) {
            $fail(__('internship.not_found'));

            return;
        }

        if (! $internship->asInternshipPeriod()->isAcceptingRegistrations()) {
            $fail(__('internship.not_accepting_registrations'));
        }
    }
}
