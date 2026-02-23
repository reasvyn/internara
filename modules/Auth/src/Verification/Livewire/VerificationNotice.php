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
        if (! auth()->check()) {
            flash()->error(__('exception::messages.unauthorized'));

            return;
        }

        try {
            $this->authService->resendVerificationEmail(auth()->user());
            flash()->success(__('auth::ui.verification.resend_success'));
        } catch (AppException $e) {
            flash()->error($e->getUserMessage());
        }
    }

    public function render()
    {
        return view('auth::livewire.verification-notice');
    }
}
