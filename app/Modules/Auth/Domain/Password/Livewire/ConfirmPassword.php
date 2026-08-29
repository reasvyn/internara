<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Password\Livewire;

use App\Modules\Auth\Domain\Password\Actions\ConfirmPasswordAction;
use App\Modules\Auth\Domain\Password\Livewire\Forms\ConfirmPasswordForm;
use App\Modules\Core\Services\SmartLogger;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;
use TallStackUi\Traits\Interactions;

class ConfirmPassword extends Component
{
    use Interactions;

    public ConfirmPasswordForm $form;

    public function confirm(ConfirmPasswordAction $action): void
    {
        $this->form->validate();

        $user = auth()->user();

        if ($user === null) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('form.password', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        try {
            $action->execute($user, $this->form->password);

            RateLimiter::clear($throttleKey);

            $this->form->reset('password');

            $this->toast()->success(__('auth.password_confirmed'))->send();

            $this->redirect($this->getIntendedUrl(), navigate: true);
        } catch (RuntimeException $e) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('form.password', $e->getMessage());

            SmartLogger::error('password_confirmation_error')
                ->event('password_confirmation_error')
                ->module('Auth')
                ->withPayload([
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ])
                ->withPiiMasking()
                ->systemOnly()
                ->save();
        }
    }

    protected function getIntendedUrl(): string
    {
        return session()->pull('url.intended', route('dashboard'));
    }

    protected function throttleKey(): string
    {
        return Str::transliterate('confirm-password|'.request()->ip());
    }

    #[Layout('auth::layouts.auth', ['title' => 'Confirm Password'])]
    public function render(): View
    {
        return view('auth.password.confirm-password');
    }
}
