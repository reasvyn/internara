<?php

declare(strict_types=1);

namespace App\Auth\AccountRecovery\Livewire;

use App\Auth\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\User\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use TallStackUi\Traits\Interactions;

class RecoverySlipManager extends Component
{
    use Interactions;

    public string $search = '';

    public ?User $selectedUser = null;

    /** @var array<int, string> */
    public array $generatedCode = [];

    public ?string $expiresAt = null;

    public function boot(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function generate(GenerateRecoverySlipAction $action): void
    {
        if (! $this->selectedUser) {
            return;
        }

        $result = $action->execute($this->selectedUser);

        $this->generatedCode = $result->data['plaintext'];
        $this->expiresAt = $result->data['expires_at'];

        $this->toast()->success(__('auth.recovery_slip_generated'))->send();
    }

    public function resetForm(): void
    {
        $this->reset(['search', 'selectedUser', 'generatedCode', 'expiresAt']);
    }

    public function selectUser(string $id): void
    {
        $this->selectedUser = User::find($id);
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        $users = [];

        if ($this->search) {
            $users = User::where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhere('username', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%");
            })
                ->orderBy('name')
                ->limit(10)
                ->get();
        }

        return view('auth.account-recovery.recovery-slip-manager', [
            'users' => $users,
        ]);
    }
}
