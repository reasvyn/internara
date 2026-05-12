<?php

declare(strict_types=1);

namespace App\Livewire\User;

use App\Actions\User\UpdateProfileAction;
use App\Actions\User\UpdateUserPasswordAction;
use App\Models\User;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileEditor extends Component
{
    use WithFileUploads;

    public $avatar;

    public User $user;

    public array $data = [];

    public array $passwordData = [
        'current_password' => '',
        'password' => '',
        'password_confirmation' => '',
    ];

    public function mount(): void
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

    public function save(UpdateProfileAction $updateProfile): void
    {
        $this->validate([
            'data.name' => 'required|string|max:255',
            'data.email' => 'required|email|unique:users,email,'.$this->user->id,
            'data.phone' => 'nullable|string|max:20',
            'data.address' => 'nullable|string|max:500',
            'data.bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $avatar = $this->avatar?->getRealPath() && file_exists($this->avatar->getRealPath()) ? $this->avatar : null;

        $updateProfile->execute(
            $this->user,
            [
                'phone' => $this->data['phone'],
                'address' => $this->data['address'],
                'bio' => $this->data['bio'],
            ],
            name: $this->data['name'],
            email: $this->data['email'],
            avatar: $avatar,
        );

        flash()->success(__('profile.saved'));
    }

    public function updatePassword(UpdateUserPasswordAction $updatePassword): void
    {
        $this->validate([
            'passwordData.current_password' => ['required', 'current_password'],
            'passwordData.password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $updatePassword->execute($this->user, $this->passwordData['password']);

        $this->reset('passwordData');
        flash()->success(__('profile.password_updated'));
    }

    public function avatarUrl(): ?string
    {
        return $this->user->getFirstMediaUrl('avatar') ?: null;
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.user.profile-editor');
    }
}
