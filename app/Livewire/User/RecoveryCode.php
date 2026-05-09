<?php

declare(strict_types=1);

namespace App\Livewire\User;

use App\Actions\Auth\GenerateRecoverySlipAction;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Mary\Traits\Toast;

class RecoveryCode extends Component
{
    use Toast;

    public ?string $generatedCode = null;

    public ?string $expiresAt = null;

    public function generate(GenerateRecoverySlipAction $action): void
    {
        $user = auth()->user();

        $result = $action->execute($user);

        $this->generatedCode = $result['plaintext'];
        $this->expiresAt = $result['code']->expires_at->format('d M Y H:i');

        $this->success('Recovery code generated successfully.');
    }

    public function resetCode(): void
    {
        $this->reset(['generatedCode', 'expiresAt']);
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.user.recovery-code');
    }
}
