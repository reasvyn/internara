<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire\Forms;

use App\Domain\User\Models\User;
use Livewire\Form;

class ProfileForm extends Form
{
    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $address = '';

    public string $bio = '';

    public ?string $employment_status = null;

    public ?string $nip = null;

    public ?string $nuptk = null;

    public ?string $competence_field = null;

    public ?string $position = null;

    public function fillFromUser(User $user): void
    {
        $profile = $user->profile;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $profile->phone ?? '';
        $this->address = $profile->address ?? '';
        $this->bio = $profile->bio ?? '';
        $this->employment_status = $profile->employment_status?->value ?? null;
        $this->nip = $profile->nip ?? null;
        $this->nuptk = $profile->nuptk ?? null;
        $this->competence_field = $profile->competence_field ?? null;
        $this->position = $profile->position ?? null;
    }
}
