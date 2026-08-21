<?php

declare(strict_types=1);

namespace App\SysAdmin\Observability\Livewire;

use App\User\Models\User;
use Illuminate\Support\Facades\Cache;
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

    #[Layout('core::layouts.app')]
    public function render(): View
    {
        $query = Activity::query()->with('causer');

        if ($this->filterUser) {
            $query->where('causer_id', $this->filterUser)->where('causer_type', User::class);
        }

        if ($this->filterModule) {
            $query->where('log_name', $this->filterModule);
        }

        if ($this->filterAction) {
            $query->where('description', $this->filterAction);
        }

        $logs = $query->latest()->paginate(20);

        $modules = Cache::remember('audit_log.modules', 300, fn () => Activity::distinct()->pluck('log_name')->filter()->sort()->values()
        );

        $actions = Cache::remember('audit_log.actions', 300, fn () => Activity::distinct()->pluck('description')->filter()->sort()->values()
        );

        $users = Cache::remember('audit_log.users', 300, fn () => User::select('id', 'name')->orderBy('name')->get()
        );

        return view('sysadmin.observability.audit-log-manager', [
            'logs' => $logs,
            'modules' => $modules,
            'actions' => $actions,
            'users' => $users,
        ]);
    }
}
