<?php

declare(strict_types=1);

namespace Modules\Status\Livewire;

use Illuminate\Pagination\Paginator;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;
use Modules\Status\Models\AccountStatusHistory;
use Modules\User\Models\User;

/**
 * Livewire component for displaying account status change history.
 *
 * Shows a visual timeline of all status transitions with full audit context:
 * who made the change, when, why, and from where (IP address).
 */
class StatusHistoryTimeline extends Component
{
    use WithPagination;

    public User $user;

    public string $sortBy = 'created_at';

    public string $sortDir = 'desc';

    public ?string $filterStatus = null;

    public ?string $filterBy = null;

    protected $queryString = [
        'sortBy' => ['except' => 'created_at'],
        'sortDir' => ['except' => 'desc'],
        'filterStatus',
        'filterBy',
        'page',
    ];

    /**
     * Mount the component.
     */
    public function mount(User $user): void
    {
        $this->user = $user;

        // Check authorization
        abort_unless(auth()->check(), 403);
        abort_unless(auth()->user()->can('viewHistory', $this->user), 403);
    }

    /**
     * Update sort direction.
     */
    public function updateSort(string $field): void
    {
        if ($this->sortBy === $field) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDir = 'desc';
        }
        $this->resetPage();
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->filterStatus = null;
        $this->filterBy = null;
        $this->resetPage();
    }

    /**
     * Get paginated history records.
     */
    public function getHistory()
    {
        $query = $this->user->statusHistory();

        if ($this->filterStatus) {
            $query->where('new_status', $this->filterStatus);
        }

        if ($this->filterBy) {
            $query->where('triggered_by_user_id', $this->filterBy);
        }

        return $query
            ->orderBy($this->sortBy, $this->sortDir)
            ->paginate(15);
    }

    /**
     * Get unique statuses for filter dropdown.
     */
    public function getStatusOptions(): array
    {
        return $this->user->statusHistory()
            ->distinct('new_status')
            ->pluck('new_status')
            ->map(fn ($status) => [
                'value' => $status,
                'label' => $status,
            ])
            ->toArray();
    }

    /**
     * Get unique admins who changed status.
     */
    public function getAdminOptions(): array
    {
        return $this->user->statusHistory()
            ->whereNotNull('triggered_by_user_id')
            ->distinct('triggered_by_user_id')
            ->with('triggeredBy')
            ->get()
            ->map(fn ($history) => [
                'value' => $history->triggered_by_user_id,
                'label' => $history->triggeredBy?->name ?? 'Unknown Admin',
            ])
            ->toArray();
    }

    /**
     * Export history to CSV.
     */
    public function exportCsv()
    {
        $records = $this->user->statusHistory()
            ->when($this->filterStatus, fn ($q) => $q->where('new_status', $this->filterStatus))
            ->when($this->filterBy, fn ($q) => $q->where('triggered_by_user_id', $this->filterBy))
            ->orderBy($this->sortBy, $this->sortDir)
            ->get();

        $csv = "Tanggal,Status Lama,Status Baru,Alasan,Oleh,Peran,Alamat IP,User Agent\n";

        foreach ($records as $history) {
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                $history->created_at->format('Y-m-d H:i:s'),
                $history->old_status ?? 'N/A',
                $history->new_status,
                $history->reason ?? '',
                $history->triggeredBy?->name ?? 'System',
                $history->triggered_by_role ?? 'System',
                $history->ip_address ?? '',
                substr($history->user_agent ?? '', 0, 100),
            );
        }

        return response()->streamDownload(
            fn () => print $csv,
            'status-history-' . $this->user->id . '-' . now()->format('Y-m-d') . '.csv',
            ['Content-Type' => 'text/csv'],
        );
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('status::livewire.status-history-timeline', [
            'history' => $this->getHistory(),
            'statusOptions' => $this->getStatusOptions(),
            'adminOptions' => $this->getAdminOptions(),
            'totalRecords' => $this->user->statusHistory()->count(),
        ]);
    }
}
