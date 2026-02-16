<?php

declare(strict_types=1);

namespace Modules\Auth\Verification\Livewire;

use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Auth\Services\Contracts\RedirectService;
use Modules\Exception\AppException;

class VerificationNotice extends Component
{
    protected AuthService $authService;

    protected RedirectService $redirectService;

    public function boot(AuthService $authService, RedirectService $redirectService)
    {
        $this->authService = $authService;
        $this->redirectService = $redirectService;
    }

    public function mount()
    {
        if (auth()->check() && auth()->user()->hasVerifiedEmail()) {
            return redirect()->intended($this->redirectService->getTargetUrl(auth()->user()));
        }
    }

    public function resend()
    {
        if (!auth()->check()) {
            session()->flash('error', 'You must be logged in to resend the verification email.');

            return;
        }

        try {
            $this->authService->resendVerificationEmail(auth()->user());
            session()->flash(
                'status',
                'A fresh verification link has been sent to your email address.',
            );
        } catch (AppException $e) {
            session()->flash('error', $e->getUserMessage());
        }
    }

    public function render()
    {
        return view('auth::livewire.verification-notice');
    }
}
