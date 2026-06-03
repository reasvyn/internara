<?php

declare(strict_types=1);

namespace App\Domain\User\Aggregates\AccountRecovery\Livewire;

use App\Domain\Core\Support\SmartLogger;
use App\Domain\User\Aggregates\AccountRecovery\Actions\RedeemRecoverySlipAction;
use App\Domain\User\Aggregates\AccountRecovery\Livewire\Forms\AccountRecoveryForm;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use RuntimeException;

class AccountRecovery extends Component
{
    public string $step = 'code';

    public AccountRecoveryForm $form;

    public function redeem(RedeemRecoverySlipAction $action): void
    {
        $this->form->validate();

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 3)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $this->addError('form.recoveryCode', __('auth.throttle', ['seconds' => $seconds]));

            return;
        }

        try {
            $action->execute(
                username: $this->form->username,
                code: $this->form->recoveryCode,
                newPassword: $this->form->password,
            );

            RateLimiter::clear($throttleKey);

            flash()->success(__('passwords.reset'));

            $this->redirectRoute('login', navigate: true);
        } catch (RuntimeException $e) {
            RateLimiter::hit($throttleKey, 300);
            $this->addError('form.recoveryCode', $e->getMessage());

            SmartLogger::error('Account recovery error')
                ->withPayload(['error' => $e->getMessage()])
                ->systemOnly()
                ->save();
        }
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(
            'account-recovery|'.Str::lower($this->form->username).'|'.request()->ip(),
        );
    }

    #[Layout('user::layouts.auth', ['title' => 'Account Recovery'])]
    public function render(): View
    {
        return view('user.account-recovery.account-recovery');
    }
}
