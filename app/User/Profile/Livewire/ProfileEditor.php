<?php

declare(strict_types=1);

namespace App\User\Profile\Livewire;

use App\Auth\Password\Actions\UpdateUserPasswordAction;
use App\Core\Livewire\BaseFormView;
use App\User\Models\User;
use App\User\Profile\Actions\ReadProfileFormAction;
use App\User\Profile\Actions\UpdateProfileAction;
use App\User\Profile\Livewire\Forms\PasswordForm;
use App\User\Profile\Livewire\Forms\ProfileForm;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;

#[Layout('core::layouts.app')]
class ProfileEditor extends BaseFormView
{
    use WithFileUploads;

    public ?UploadedFile $avatar = null;

    public User $user;

    public ProfileForm $profileForm;

    public PasswordForm $passwordForm;

    public bool $canChangeName = true;

    public bool $canChangeUsername = true;

    public bool $isStaff = false;

    /** @var string[] */
    public array $staffFields = [];

    public function mount(ReadProfileFormAction $action): void
    {
        $this->user = auth()
            ->user()
            ->load(['profile', 'roles']);

        $formData = $action->execute($this->user);

        $this->canChangeName = $formData['canChangeName'];
        $this->canChangeUsername = $formData['canChangeUsername'];
        $this->isStaff = $formData['staffFields'] !== [];
        $this->staffFields = $formData['staffFields'];

        $this->profileForm->fillFromUser($this->user);
    }

    public function getIdNumberLabel(): string
    {
        $user = $this->user;

        if ($user->hasRole('student')) {
            return __('profile.id_number_student');
        }

        if ($user->hasRole('teacher')) {
            return __('profile.id_number_teacher');
        }

        if ($user->hasRole('supervisor')) {
            return __('profile.id_number_supervisor');
        }

        return __('profile.id_number');
    }

    public bool $showConfirm = false;

    public function updatedAvatar(): void
    {
        $this->authorize('update', $this->user);

        $this->validate(['avatar' => ['nullable', 'image', 'max:2048']]);

        app(UpdateProfileAction::class)->execute($this->user, [], avatar: $this->avatar);

        flash()->success(__('profile.avatar_saved'));
    }

    public function confirmRemoveAvatar(): void
    {
        $this->authorize('update', $this->user);

        $this->user->clearMediaCollection('avatar');
        $this->avatar = null;

        flash()->success(__('profile.avatar_removed'));
    }

    public function save(UpdateProfileAction $updateProfile): void
    {
        $this->authorize('update', $this->user);

        $rules = [
            'profileForm.email' => 'required|email|unique:users,email,' . $this->user->id,
            'profileForm.phone' => 'nullable|string|max:20',
            'profileForm.address' => 'nullable|string|max:500',
            'profileForm.bio' => 'nullable|string|max:1000',
        ];

        if ($this->canChangeName) {
            $rules['profileForm.name'] = 'required|string|max:255';
        }

        if ($this->canChangeUsername) {
            $rules['profileForm.username'] =
                'required|string|alpha_num|lowercase|max:50|unique:users,username,' .
                $this->user->id;
        }

        if ($this->isStaff) {
            $profileId = $this->user->profile?->id ?? 'NULL';
            $rules = array_merge($rules, [
                'profileForm.id_number' => "nullable|string|max:30|unique:profiles,id_number,{$profileId}",
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
                'id_number' => $this->profileForm->id_number,
                'competence_field' => $this->profileForm->competence_field,
                'job_title' => $this->profileForm->job_title,
            ]);
        }

        $updateProfile->execute(
            $this->user,
            $data,
            name: $this->canChangeName ? $this->profileForm->name : null,
            email: $this->profileForm->email,
            username: $this->canChangeUsername ? $this->profileForm->username : null,
        );

        flash()->success(__('profile.saved'));
    }

    public function confirmAction(): void
    {
        $this->confirmRemoveAvatar();
        $this->showConfirm = false;
    }

    public function updatePassword(UpdateUserPasswordAction $updatePassword): void
    {
        $this->authorize('update', $this->user);

        $this->passwordForm->validate();

        $throttleKey = $this->passwordThrottleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError(
                'passwordForm.current_password',
                __('auth.throttle', ['seconds' => $seconds]),
            );

            return;
        }

        $updatePassword->execute($this->user, $this->passwordForm->password);

        RateLimiter::clear($throttleKey);

        $this->passwordForm->resetForm();
        flash()->success(__('profile.password_updated'));
    }

    protected function passwordThrottleKey(): string
    {
        return Str::transliterate('change-password|' . $this->user->id . '|' . request()->ip());
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
        return view('user.profile.profile-editor');
    }
}
