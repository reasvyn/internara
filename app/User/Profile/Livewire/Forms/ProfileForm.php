<?php

declare(strict_types=1);

namespace App\User\Profile\Livewire\Forms;

use App\User\Models\User;
use Livewire\Form;

class ProfileForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $username = '';

    public string $phone = '';

    public string $address = '';

    public string $bio = '';

    public ?string $employment_status = null;

    public ?string $employee_id_number = null;

    public ?string $educator_id_number = null;

    public ?string $competence_field = null;

    public ?string $job_title = null;

    public function fillFromUser(User $user): void
    {
        $profile = $user->profile;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;
        $this->phone = $profile->phone ?? '';
        $this->address = $profile->address ?? '';
        $this->bio = $profile->bio ?? '';
        $this->employment_status = $profile->employment_status?->value ?? null;
        $this->employee_id_number = $profile->employee_id_number ?? null;
        $this->educator_id_number = $profile->educator_id_number ?? null;
        $this->competence_field = $profile->competence_field ?? null;
        $this->job_title = $profile->job_title ?? null;
    }
}
