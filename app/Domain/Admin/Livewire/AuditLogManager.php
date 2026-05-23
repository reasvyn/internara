<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire;

use App\Domain\User\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class AuditLogManager extends Component
{
    use WithPagination;

    public ?string $filterUser = null;

    public ?string $filterModule = null;

    public ?string $filterAction = null;

    public function resetFilters(): void
    {
        $this->filterUser = null;
        $this->filterModule = null;
        $this->filterAction = null;
        $this->resetPage();
    }

    #[Layout('shared::layouts.app')]
    public function render(): View
    {
        $query = Activity::query()->with('causer');

        if ($this->filterUser) {
            $query->where('causer_id', $this->filterUser)
                ->where('causer_type', User::class);
        }

        if ($this->filterModule) {
            $query->where('log_name', $this->filterModule);
        }

        if ($this->filterAction) {
            $query->where('description', $this->filterAction);
        }

        $logs = $query->latest()->paginate(20);

        $modules = Activity::distinct()->pluck('log_name')->filter()->sort()->values();
        $actions = Activity::distinct()->pluck('description')->filter()->sort()->values();
        $users = User::orderBy('name')->get();

        return view('admin.audit-log-manager', [
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'users' => $users,
        ]);
    }
}
