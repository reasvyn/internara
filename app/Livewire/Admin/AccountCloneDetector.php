<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Actions\User\DetectUserAccountCloneAction;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AccountCloneDetector extends Component
{
    public array $clones = [];

    public function mount(DetectUserAccountCloneAction $action): void
    {
        Gate::authorize('viewLifecycleDashboard', User::class);
        $this->clones = $action->execute();
    }

    public function render(): View
    {
        return view('livewire.admin.accounts.clones', [
            'clones' => $this->clones,
        ]);
    }
}
