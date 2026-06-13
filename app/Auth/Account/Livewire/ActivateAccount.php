<?php

declare(strict_types=1);

namespace App\Auth\Account\Livewire;

use App\Auth\Account\Entities\AccountActivation;
use App\Auth\ApiTokens\Models\ApiToken;
use App\User\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ActivateAccount extends Component
{
    public string $email = '';

    public string $code = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function activate(): void
    {
        $this->validate([
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|min:16|max:19',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('code', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        $user = User::where('email', $this->email)->first();

        if (! $user) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('email', __('auth.activate.invalid_email'));

            return;
        }

        $activation = AccountActivation::forUser($user);

        if (! $activation->isTokenValid()) {
            $this->addError('code', __('auth.activate.invalid_code'));

            return;
        }

        if (! ApiToken::verify($user, 'activation', $this->code)) {
            RateLimiter::hit($throttleKey, 300);

            $this->addError('code', __('auth.activate.invalid_code'));

            return;
        }

        ApiToken::revokeFor($user, 'activation');

        $user->update([
            'password' => Hash::make($this->password),
        ]);

        RateLimiter::clear($throttleKey);

        auth()->login($user);

        flash()->success(__('auth.activate.success'));

        $this->redirectRoute('dashboard', navigate: true);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate('activate|'.$this->email.'|'.request()->ip());
    }

    #[Layout('auth::layouts.auth', ['title' => 'Activate Account'])]
    public function render(): View
    {
        return view('auth.activation-token.activate-account');
    }
}
