<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire;

use App\Domain\Auth\Actions\ConfirmPasswordAction;
use App\Domain\Core\Support\SmartLogger;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;
use RuntimeException;

class ConfirmPassword extends Component
{
    #[Validate('required|string')]
    public string $password = '';

    public function confirm(ConfirmPasswordAction $action): void
    {
        $this->validate();

        $user = auth()->user();

        if ($user === null) {
            $this->redirectRoute('login', navigate: true);

            return;
        }

        try {
            $action->execute($user, $this->password);

            $this->reset('password');

            flash()->success(__('auth.password_confirmed') ?? 'Password confirmed.');

            $this->redirect($this->getIntendedUrl(), navigate: true);
        } catch (RuntimeException $e) {
            $this->addError('password', $e->getMessage());

            SmartLogger::error('Password confirmation error')
                ->withPayload([
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ])
                ->systemOnly()
                ->save();
        }
    }

    protected function getIntendedUrl(): string
    {
        return session()->pull('url.intended', route('dashboard'));
    }

    #[Layout('auth::layouts.auth', ['title' => 'Confirm Password'])]
    public function render(): View
    {
        return view('auth.confirm-password');
    }
}
