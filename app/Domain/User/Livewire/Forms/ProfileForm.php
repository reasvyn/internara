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

    public function fillFromUser(User $user): void
    {
        $profile = $user->profile;

        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $profile->phone ?? '';
        $this->address = $profile->address ?? '';
        $this->bio = $profile->bio ?? '';
    }
}
