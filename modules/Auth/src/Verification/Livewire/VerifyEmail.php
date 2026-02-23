<?php

declare(strict_types=1);

namespace Modules\Auth\Verification\Livewire;

use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;
use Modules\Auth\Services\Contracts\RedirectService;
use Modules\User\Models\User;

class VerifyEmail extends Component
{
    public $id;

    public $hash;

    protected AuthService $authService;

    protected RedirectService $redirectService;

    public function mount($id, $hash)
    {
        $this->id = $id;
        $this->hash = $hash;
    }

    public function boot(AuthService $authService, RedirectService $redirectService)
    {
        $this->authService = $authService;
        $this->redirectService = $redirectService;
    }

    public function verify()
    {
        if (! auth()->check()) {
            return redirect()->route('login');
        }

        // Ensure the authenticated user is the one being verified
        if ((string) auth()->id() !== (string) $this->id) {
            flash()->error(__('exception::messages.unauthorized'));

            return redirect()->route('verification.notice');
        }

        if ($this->authService->verifyEmail($this->id, $this->hash)) {
            flash()->success(__('auth::ui.verification.success'));

            return redirect()->intended($this->redirectService->getTargetUrl(auth()->user()));
        }

        flash()->error(__('auth::exceptions.invalid_verification_link'));

        return redirect()->route('verification.notice');
    }

    public function resend()
    {
        if (auth()->check() && ! auth()->user()->hasVerifiedEmail()) {
            $this->authService->resendVerificationEmail(auth()->user());
            flash()->success(__('auth::ui.verification.resend_success'));
        } else {
            flash()->error(__('auth::exceptions.verification_resend_error'));
        }

        return redirect()->back();
    }

    public function render()
    {
        return view('auth::livewire.verify-email');
    }
}
