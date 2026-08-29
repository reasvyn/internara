<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Observability\Livewire;

use App\Modules\User\Domain\AccountStatus\Actions\DetectUserAccountCloneAction;
use App\Modules\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class AccountCloneDetector extends Component
{
    public $clones = [];

    public function mount(DetectUserAccountCloneAction $action): void
    {
        Gate::authorize('viewLifecycleDashboard', User::class);
        $this->clones = $action->execute();
    }

    public function render(): View
    {
        return view('sysadmin.observability.accounts-clones', [
            'clones' => $this->clones,
        ]);
    }
}
