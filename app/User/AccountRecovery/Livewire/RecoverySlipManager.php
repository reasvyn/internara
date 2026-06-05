<?php

declare(strict_types=1);

namespace App\User\AccountRecovery\Livewire;

use App\User\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\User\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RecoverySlipManager extends Component
{
    public string $search = '';

    public ?User $selectedUser = null;

    /** @var array<int, string> */
    public array $generatedCode = [];

    public ?string $expiresAt = null;

    public function boot(): void
    {
        if (
            ! auth()
                ->user()
                ?->hasAnyRole(['super_admin', 'admin'])
        ) {
            abort(403);
        }
    }

    public function generate(GenerateRecoverySlipAction $action): void
    {
        if (! $this->selectedUser) {
            return;
        }

        $result = $action->execute($this->selectedUser);

        $this->generatedCode = $result['plaintext'];
        $this->expiresAt = $result['expires_at'];

        flash()->success(__('auth.recovery_slip_generated'));
    }

    public function resetForm(): void
    {
        $this->reset(['search', 'selectedUser', 'generatedCode', 'expiresAt']);
    }

    public function selectUser(string $id): void
    {
        $this->selectedUser = User::find($id);
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        $users = [];

        if ($this->search) {
            $users = User::where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            })->orderBy('name')->limit(10)->get();
        }

        return view('user.account-recovery.recovery-slip-manager', [
            'users' => $users,
        ]);
    }
}
