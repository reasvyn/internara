<?php

declare(strict_types=1);

namespace App\Livewire\Profile;

use App\Actions\Auth\UpdatePasswordAction;
use App\Actions\Profile\UpdateProfileAction;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

class ProfileEditor extends Component
{
    use Toast;

    public User $user;

    public array $data = [];

    public array $passwordData = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    /**
     * Initialize the component.
     */
    public function mount()
    {
        $this->user = auth()->user()->load(['profile', 'roles']);

        $profile = $this->user->profile;

        $this->data = [
            'name' => $this->user->name,
            'email' => $this->user->email,
            'phone' => $profile->phone ?? '',
            'address' => $profile->address ?? '',
            'bio' => $profile->bio ?? '',
        ];
    }

    /**
     * Save the profile changes.
     */
    public function save(UpdateProfileAction $updateProfile)
    {
        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.email' => 'required|email|unique:users,email,'.$this->user->id,
            'data.phone' => 'nullable|string|max:20',
            'data.address' => 'nullable|string|max:500',
            'data.bio' => 'nullable|string|max:1000',
        ]);

        $this->user->update([
            'name' => $this->data['name'],
            'email' => $this->data['email'],
        ]);

        $updateProfile->execute($this->user, [
            'phone' => $this->data['phone'],
            'address' => $this->data['address'],
            'bio' => $this->data['bio'],
        ]);

        $this->success('Profile updated successfully.');
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(UpdatePasswordAction $updatePassword)
    {
        $this->validate([
            'passwordData.current_password' => ['required', 'current_password'],
            'passwordData.password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $updatePassword->execute($this->user, $this->passwordData['password']);

        $this->reset('passwordData');
        $this->success('Password updated successfully.');
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.profile.profile-editor');
    }
}
