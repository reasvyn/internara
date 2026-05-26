<?php

declare(strict_types=1);

namespace App\Domain\User\Livewire;

use App\Domain\Auth\Actions\UpdateUserPasswordAction;
use App\Domain\User\Actions\GetProfileFormDataAction;
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

#[Layout('shared::layouts.app')]
class ProfileEditor extends Component
{
    use WithFileUploads;

    public ?UploadedFile $avatar = null;

    public User $user;

    public ProfileForm $profileForm;

    public PasswordForm $passwordForm;

    public bool $canChangeName = true;

    public bool $isStaff = false;

    /** @var string[] */
    public array $staffFields = [];

    public function mount(GetProfileFormDataAction $action): void
    {
        $this->user = auth()->user()->load(['profile', 'roles']);

        $formData = $action->execute($this->user);

        $this->canChangeName = $formData['canChangeName'];
        $this->isStaff = $formData['staffFields'] !== [];
        $this->staffFields = $formData['staffFields'];

        $this->profileForm->fillFromUser($this->user);
    }

    public function save(UpdateProfileAction $updateProfile): void
    {
        $rules = [
            'profileForm.email' => 'required|email|unique:users,email,'.$this->user->id,
            'profileForm.phone' => 'nullable|string|max:20',
            'profileForm.address' => 'nullable|string|max:500',
            'profileForm.bio' => 'nullable|string|max:1000',
            'avatar' => 'nullable|image|max:2048',
        ];

        if ($this->canChangeName) {
            $rules['profileForm.name'] = 'required|string|max:255';
        }

        if ($this->isStaff) {
            $profileId = $this->user->profile?->id ?? 'NULL';
            $rules = array_merge($rules, [
                'profileForm.nip' => "nullable|string|max:18|unique:profiles,nip,{$profileId}",
                'profileForm.nuptk' => "nullable|string|max:16|unique:profiles,nuptk,{$profileId}",
                'profileForm.competence_field' => 'nullable|string|max:255',
            ]);
        }

        $this->validate($rules);

        $data = [
            'phone' => $this->profileForm->phone,
            'address' => $this->profileForm->address,
            'bio' => $this->profileForm->bio,
        ];

        if ($this->isStaff) {
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
            name: $this->canChangeName ? $this->profileForm->name : null,
            email: $this->profileForm->email,
            avatar: $this->avatar,
        );

        flash()->success(__('profile.saved'));
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

    public function render(): View
    {
        return view('user.profile-editor');
    }
}
