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
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Ensure the authenticated user is the one being verified
        if ((string) auth()->id() !== (string) $this->id) {
            session()->flash('error', 'Tindakan ini tidak sah.');

            return redirect()->route('verification.notice');
        }

        if ($this->authService->verifyEmail($this->id, $this->hash)) {
            session()->flash('status', 'Email Anda telah berhasil diverifikasi!');

            return redirect()->intended($this->redirectService->getTargetUrl(auth()->user()));
        }

        session()->flash('error', 'Tautan verifikasi tidak valid atau email sudah diverifikasi.');

        return redirect()->route('verification.notice');
    }

    public function resend()
    {
        if (auth()->check() && !auth()->user()->hasVerifiedEmail()) {
            $this->authService->resendVerificationEmail(auth()->user());
            session()->flash(
                'status',
                'A fresh verification link has been sent to your email address.',
            );
        } else {
            session()->flash('error', 'You are already verified or not logged in.');
        }

        return redirect()->back();
    }

    public function render()
    {
        return view('auth::livewire.verify-email');
    }
}
