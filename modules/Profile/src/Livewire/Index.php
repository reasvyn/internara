<?php

declare(strict_types=1);

namespace Modules\Profile\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Exception\Concerns\HandlesAppException;
use Modules\Profile\Services\Contracts\ProfileService;
use Modules\User\Models\User;
use Modules\User\Services\Contracts\UserService;

/**
 * Class Index
 *
 * Dashboard for users to manage their own profile and security settings.
 */
class Index extends Component
{
    use HandlesAppException;
    use WithFileUploads;

    /**
     * User data.
     */
    public string $name;

    public string $email;

    public string $username;

    public $avatar;

    /**
     * Profile data.
     */
    public ?string $phone = null;

    public ?string $address = null;

    public ?string $bio = null;

    public ?string $nip = null;

    public ?string $nisn = null;

    /**
     * Security data.
     */
    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Services.
     */
    protected UserService $userService;

    protected ProfileService $profileService;

    /**
     * Boot services.
     */
    public function boot(UserService $userService, ProfileService $profileService): void
    {
        $this->userService = $userService;
        $this->profileService = $profileService;
    }

    /**
     * Initialize component.
     */
    public function mount(): void
    {
        /** @var User $user */
        $user = auth()->user();
        $profile = $this->profileService->getByUserId($user->id);

        $this->name = $user->name;
        $this->email = $user->email;
        $this->username = $user->username;

        $this->phone = $profile->phone;
        $this->address = $profile->address;
        $this->bio = $profile->bio;
        $this->nip = $profile->nip;
        $this->nisn = $profile->nisn;
    }

    /**
     * Save basic profile info.
     */
    public function saveInfo(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:users,username,'.auth()->id(),
            'email' => 'required|email|unique:users,email,'.auth()->id(),
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'bio' => 'nullable|string|max:1000',
        ]);

        try {
            $this->userService->update(auth()->id(), [
                'name' => $this->name,
                'username' => $this->username,
                'email' => $this->email,
            ]);

            $this->profileService->update(auth()->user()->profile->id, [
                'phone' => $this->phone,
                'address' => $this->address,
                'bio' => $this->bio,
            ]);

            $this->dispatch('success', message: __('Profile updated successfully.'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Save role-specific fields.
     */
    public function saveSpecialFields(): void
    {
        /** @var User $user */
        $user = auth()->user();

        $rules = [];
        if ($user->hasRole(\Modules\Permission\Enums\Role::TEACHER->value)) {
            $rules['nip'] = 'required|string|unique:profiles,nip,'.$user->profile->id;
        }
        if ($user->hasRole(\Modules\Permission\Enums\Role::STUDENT->value)) {
            $rules['nisn'] = 'required|string|unique:profiles,nisn,'.$user->profile->id;
        }

        if (empty($rules)) {
            return;
        }

        $data = $this->validate($rules);

        try {
            $this->profileService->update($user->profile->id, $data);
            $this->dispatch('success', message: __('Special fields updated.'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Update password.
     */
    public function savePassword(): void
    {
        $this->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->userService->update(auth()->id(), [
                'password' => $this->password,
            ]);

            $this->password = '';
            $this->password_confirmation = '';
            $this->dispatch('success', message: __('Password updated successfully.'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Update avatar.
     */
    public function updatedAvatar(): void
    {
        $this->validate(['avatar' => 'image|max:1024']);

        try {
            /** @var User $user */
            $user = auth()->user();
            $user->setAvatar($this->avatar);
            $this->dispatch('success', message: __('Avatar updated.'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('profile::livewire.index');
    }
}
