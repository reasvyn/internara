<?php

declare(strict_types=1);

namespace Modules\Auth\Livewire;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Livewire\Component;
use Modules\Auth\Services\Contracts\AuthService;

class ResetPassword extends Component
{
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    protected AuthService $authService;

    public function boot(AuthService $authService): void
    {
        $this->authService = $authService;
    }

    public function mount(Request $request, string $token): void
    {
        $this->token = $token;
        $this->email = (string) $request->query('email', '');
    }

    public function rules(): array
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            'password' => [
                'required',
                'string',
                'confirmed',
                \Modules\Shared\Rules\Password::auto(),
            ],
        ];
    }

    public function resetPassword(): void
    {
        $this->validate();

        $success = $this->authService->resetPassword([
            'token' => $this->token,
            'email' => $this->email,
            'password' => $this->password,
            'password_confirmation' => $this->password_confirmation,
        ]);

        if ($success) {
            flash()->success(__('auth::ui.reset_password.success'));

            $this->redirect(route('login'), navigate: true);
        } else {
            $this->addError('email', trans('passwords.token'));
        }
    }

    public function render(): View
    {
        return view('auth::livewire.reset-password')->layout('auth::components.layouts.auth', [
            'title' => __('auth::ui.reset_password.title').' | '.setting('site_title', 'Internara'),
        ]);
    }
}
