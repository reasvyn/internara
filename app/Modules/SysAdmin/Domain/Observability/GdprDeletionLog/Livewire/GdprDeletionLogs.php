<?php

declare(strict_types=1);

namespace App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Livewire;

use App\Modules\Core\Livewire\Concerns\WithSorting;
use App\Modules\SysAdmin\Domain\Observability\GdprDeletionLog\Models\GdprDeletionLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class GdprDeletionLogs extends Component
{
    use WithPagination, WithSorting;

    public string $search = '';

    public string $filterType = '';

    public function headers(): array
    {
        return [
            ['index' => 'user_email', 'label' => 'Email', 'sortable' => true],
            ['index' => 'deletion_type', 'label' => 'Type'],
            ['index' => 'reason', 'label' => 'Reason'],
            ['index' => 'deleted_at', 'label' => 'Deleted At', 'sortable' => true],
        ];
    }

    public function logs()
    {
        return GdprDeletionLog::query()
            ->when(
                $this->search,
                fn (Builder $q) => $q->where('user_email', 'like', "%{$this->search}%"),
            )
            ->when(
                $this->filterType,
                fn (Builder $q) => $q->where('deletion_type', $this->filterType),
            )
            ->latest('deleted_at')
            ->paginate(20);
    }

    #[Layout('ui::layouts.app')]
    public function render(): View
    {
        return view('sysadmin.observability.gdpr-deletion-log.gdpr-deletion-logs', [
            'logs' => $this->logs(),
            'headers' => $this->headers(),
        ]);
    }
}
