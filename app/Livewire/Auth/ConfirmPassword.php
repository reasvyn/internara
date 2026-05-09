<?php

declare(strict_types=1);

namespace App\Livewire\Auth;

use App\Actions\Auth\ConfirmPasswordAction;
use Illuminate\Support\Facades\Log;
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

            Log::error('Password confirmation error: '.$e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    protected function getIntendedUrl(): string
    {
        return session()->pull('url.intended', route('dashboard'));
    }

    #[Layout('layouts::auth', ['title' => 'Confirm Password'])]
    public function render(): View
    {
        return view('auth.confirm-password');
    }
}
