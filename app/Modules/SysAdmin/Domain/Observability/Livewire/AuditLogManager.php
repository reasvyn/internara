<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Observability\Livewire;

use App\Modules\UI\Livewire\BaseRecordManager;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Spatie\Activitylog\Models\Activity;

class AuditLogManager extends BaseRecordManager
{
    public function headers(): array
    {
        return [
            ['index' => 'created_at', 'label' => __('sysadmin.activity_timestamp'), 'sortable' => true],
            ['index' => 'causer.name', 'label' => __('sysadmin.activity_user')],
            ['index' => 'description', 'label' => __('sysadmin.activity_action'), 'sortable' => true],
            ['index' => 'log_name', 'label' => __('sysadmin.activity_module')],
            ['index' => 'subject', 'label' => __('sysadmin.activity_subject')],
            ['index' => 'ip', 'label' => __('sysadmin.activity_ip')],
        ];
    }

    protected function query(): Builder
    {
        return Activity::query()->with('causer');
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where(function (Builder $q): void {
            $q->where('description', 'like', "%{$this->search}%")
                ->orWhere('log_name', 'like', "%{$this->search}%")
                ->orWhereHas('causer', fn ($c) => $c->where('name', 'like', "%{$this->search}%"));
        });
    }

    protected function applyFilters(Builder $query): Builder
    {
        return $query
            ->when($this->filters['user'] ?? null, fn ($q, $u) => $q->where('causer_id', $u)->where('causer_type', User::class))
            ->when($this->filters['module'] ?? null, fn ($q, $m) => $q->where('log_name', $m))
            ->when($this->filters['action'] ?? null, fn ($q, $a) => $q->where('description', $a));
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        $modules = Cache::remember(
            config('cache-keys.audit_log_modules'),
            300,
            fn () => Activity::distinct()->pluck('log_name')->filter()->sort()->values()
        );

        $actions = Cache::remember(
            config('cache-keys.audit_log_actions'),
            300,
            fn () => Activity::distinct()->pluck('description')->filter()->sort()->values()
        );

        $users = Cache::remember(
            config('cache-keys.audit_log_users'),
            300,
            fn () => User::select('id', 'name')->orderBy('name')->get()
        );

        return view('sysadmin.observability.audit-log-manager', [
            'logs' => $this->rows(),
            'modules' => $modules,
            'actions' => $actions,
            'users' => $users,
        ]);
    }
}
