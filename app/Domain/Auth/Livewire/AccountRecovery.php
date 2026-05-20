<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire;

use App\Domain\Auth\Actions\RedeemRecoverySlipAction;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

class AccountRecovery extends Component
{
    public string $step = 'code';

    public string $username = '';

    public string $recoveryCode = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function redeem(RedeemRecoverySlipAction $action): void
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('recoveryCode', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        try {
            $action->execute(
                username: $this->username,
                code: $this->recoveryCode,
                newPassword: $this->password,
            );

            RateLimiter::clear($throttleKey);

            flash()->success(__('passwords.reset'));

            $this->redirectRoute('login', navigate: true);
        } catch (RuntimeException $e) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('recoveryCode', $e->getMessage());

            SmartLogger::error('Account recovery error')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
        }
    }

    public function rules(): array
    {
        return [
            'username' => 'required|string',
            'recoveryCode' => 'required|string|size:12',
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ];
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            'account-recovery|'.Str::lower($this->username).'|'.request()->ip(),
        );
    }

    #[Layout('auth::layouts.auth', ['title' => 'Account Recovery'])]
    public function render(): View
    {
        return view('auth.account-recovery');
    }
}
