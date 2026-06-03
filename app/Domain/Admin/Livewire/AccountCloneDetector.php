<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\User\Aggregates\AccountStatus\Actions\DetectUserAccountCloneAction;
use App\Domain\User\Models\User;
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
        return view('admin.accounts-clones', [
            'clones' => $this->clones,
        ]);
    }
}
