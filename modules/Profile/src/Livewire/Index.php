<?php

declare(strict_types=1);

namespace Modules\Profile\Livewire;

use Livewire\Attributes\Url;
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
     * Current active tab.
     */
    #[Url]
    public string $tab = 'info';

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

    /**
     * Enhanced Profile fields
     */
    public ?string $gender = null;

    public ?string $blood_type = null;

    public ?string $emergency_contact_name = null;

    public ?string $emergency_contact_phone = null;

    public ?string $emergency_contact_address = null;

    /**
     * Special Identity fields
     */
    public ?string $nip = null;

    public ?string $national_identifier = null;

    public ?string $registration_number = null;

    public ?string $class_name = null;

    public $passport_photo;

    /**
     * Security data.
     */
    public string $current_password = '';

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
        $this->gender = $profile->gender;
        $this->blood_type = $profile->blood_type;
        $this->emergency_contact_name = $profile->emergency_contact_name;
        $this->emergency_contact_phone = $profile->emergency_contact_phone;
        $this->emergency_contact_address = $profile->emergency_contact_address;

        if ($profile->profileable_type === \Modules\Teacher\Models\Teacher::class) {
            $this->nip = $profile->profileable->nip;
        }

        if ($profile->profileable_type === \Modules\Student\Models\Student::class) {
            $this->national_identifier = $profile->profileable->national_identifier;
            $this->registration_number = $profile->profileable->registration_number;
            $this->class_name = $profile->profileable->class_name;
        }
    }

    /**
     * Save basic profile info.
     */
    public function saveInfo(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.auth()->id(),
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'gender' => [
                'nullable',
                'string',
                \Illuminate\Validation\Rule::enum(\Modules\Profile\Enums\Gender::class),
            ],
            'blood_type' => [
                'nullable',
                'string',
                \Illuminate\Validation\Rule::enum(\Modules\Profile\Enums\BloodType::class),
            ],
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_address' => 'nullable|string',
            'bio' => 'nullable|string|max:1000',
        ]);

        try {
            $userData = [
                'email' => $this->email,
            ];

            // Only update name if not SuperAdmin (since UI is display-only for them)
            if (! auth()->user()->hasRole('super-admin')) {
                $userData['name'] = $this->name;
            }

            $this->userService->update(auth()->id(), $userData);

            $this->profileService->update(auth()->user()->profile->id, [
                'phone' => $this->phone,
                'address' => $this->address,
                'gender' => $this->gender,
                'blood_type' => $this->blood_type,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'emergency_contact_address' => $this->emergency_contact_address,
                'bio' => $this->bio,
            ]);

            flash()->success(__('profile::ui.messages.profile_updated'));
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
        $profileable = $user->profile->profileable;

        if (! $profileable) {
            return;
        }

        try {
            if ($user->hasRole(\Modules\Permission\Enums\Role::TEACHER->value)) {
                $this->validate([
                    'nip' => 'required|string|unique:teachers,nip,'.$profileable->id,
                ]);
                $profileable->update(['nip' => $this->nip]);
            }

            if ($user->hasRole(\Modules\Permission\Enums\Role::STUDENT->value)) {
                $this->validate([
                    'national_identifier' => 'required|string|unique:students,national_identifier,'.$profileable->id,
                    'registration_number' => 'nullable|string|unique:students,registration_number,'.$profileable->id,
                    'class_name' => 'nullable|string|max:50',
                    'passport_photo' => 'nullable|image|max:1024',
                ]);

                $profileable->update([
                    'national_identifier' => $this->national_identifier,
                    'registration_number' => $this->registration_number,
                    'class_name' => $this->class_name,
                ]);

                if ($this->passport_photo) {
                    $profileable->setMedia(
                        $this->passport_photo,
                        \Modules\Student\Models\Student::COLLECTION_PASSPORT_PHOTO,
                    );
                    $this->passport_photo = null;
                }
            }

            flash()->success(__('Special fields updated.'));
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
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $this->userService->update(auth()->id(), [
                'password' => $this->password,
            ]);

            $this->current_password = '';
            $this->password = '';
            $this->password_confirmation = '';
            flash()->success(__('profile::ui.messages.password_updated'));
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

            $this->avatar = null;
            flash()->success(__('profile::ui.messages.avatar_updated'));
        } catch (\Throwable $e) {
            $this->handleAppExceptionInLivewire($e);
        }
    }

    /**
     * Render the component.
     */
    public function render()
    {
        return view('profile::livewire.index')->layout('ui::components.layouts.dashboard', [
            'title' => __('profile::ui.profile_settings').
                ' | '.
                setting('brand_name', setting('app_name')),
        ]);
    }
}
