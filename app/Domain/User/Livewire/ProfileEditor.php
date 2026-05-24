<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire;

use App\Domain\Auth\Actions\UpdateUserPasswordAction;
use App\Domain\User\Actions\UpdateProfileAction;
use App\Domain\User\Livewire\Forms\PasswordForm;
use App\Domain\User\Livewire\Forms\ProfileForm;
use App\Domain\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileEditor extends Component
{
    use WithFileUploads;

    public ?UploadedFile $avatar = null;

    public User $user;

    public ProfileForm $profileForm;

    public PasswordForm $passwordForm;

    public function mount(): void
    {
        $this->user = auth()->user()->load(['profile', 'roles']);

        $this->profileForm->fillFromUser($this->user);
    }

    public function save(UpdateProfileAction $updateProfile): void
    {
        $rules = [
            'profileForm.name' => 'required|string|max:255',
            'profileForm.email' => 'required|email|unique:users,email,'.$this->user->id,
            'profileForm.phone' => 'nullable|string|max:20',
            'profileForm.address' => 'nullable|string|max:500',
            'profileForm.bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:2048',
        ];

        if ($this->isStaff()) {
            $rules = array_merge($rules, [
                'profileForm.nip' => 'nullable|string|max:18|unique:profiles,nip,'.($this->user->profile?->id ?? 'NULL'),
                'profileForm.nuptk' => 'nullable|string|max:16|unique:profiles,nuptk,'.($this->user->profile?->id ?? 'NULL'),
                'profileForm.competence_field' => 'nullable|string|max:255',
            ]);
        }

        $this->validate($rules);

        $data = [
            'phone' => $this->profileForm->phone,
            'address' => $this->profileForm->address,
            'bio' => $this->profileForm->bio,
        ];

        if ($this->isStaff()) {
            $data = array_merge($data, [
                'employment_status' => $this->profileForm->employment_status,
                'nip' => $this->profileForm->nip,
                'nuptk' => $this->profileForm->nuptk,
                'competence_field' => $this->profileForm->competence_field,
                'position' => $this->profileForm->position,
            ]);
        }

        $updateProfile->execute(
            $this->user,
            $data,
            name: $this->profileForm->name,
            email: $this->profileForm->email,
            avatar: $this->avatar,
        );

        flash()->success(__('profile.saved'));
    }

    public function isStaff(): bool
    {
        return $this->user->hasAnyRole(['super_admin', 'admin', 'teacher']);
    }

    public function updatePassword(UpdateUserPasswordAction $updatePassword): void
    {
        $this->passwordForm->validate();

        $throttleKey = $this->passwordThrottleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('passwordForm.current_password', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        $updatePassword->execute($this->user, $this->passwordForm->password);

        RateLimiter::clear($throttleKey);

        $this->passwordForm->resetForm();
        flash()->success(__('profile.password_updated'));
    }

    protected function passwordThrottleKey(): string
    {
        return Str::transliterate(
            'change-password|'.$this->user->id.'|'.request()->ip(),
        );
    }

    public function avatarPreviewUrl(): ?string
    {
        if ($this->avatar === null) {
            return null;
        }

        try {
            return $this->avatar->temporaryUrl();
        } catch (\Throwable) {
            return null;
        }
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        return view('user.profile-editor');
    }
}
