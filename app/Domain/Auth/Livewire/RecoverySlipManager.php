<?php

declare(strict_types=1);

namespace App\Domain\Auth\Livewire;

use App\Domain\Auth\Actions\GenerateRecoverySlipAction;
use App\Domain\User\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RecoverySlipManager extends Component
{
    public string $search = '';

    public ?User $selectedUser = null;

    public ?string $generatedCode = null;

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
        $this->expiresAt = $result['code']->expires_at->format('d M Y H:i');

        flash()->success('Recovery slip generated successfully.');
    }

    public function resetForm(): void
    {
        $this->reset(['search', 'selectedUser', 'generatedCode', 'expiresAt']);
    }

    public function selectUser(string $id): void
    {
        $this->selectedUser = User::find($id);
    }

    #[Layout('layouts::app')]
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

        return view('auth.recovery-slip-manager', [
            'users' => $users,
        ]);
    }
}
