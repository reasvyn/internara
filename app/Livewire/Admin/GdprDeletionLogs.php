<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\GdprDeletionLog;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class GdprDeletionLogs extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    public function headers(): array
    {
        return [
            ['key' => 'user_email', 'label' => 'Email', 'sortable' => true],
            ['key' => 'deletion_type', 'label' => 'Type'],
            ['key' => 'reason', 'label' => 'Reason'],
            ['key' => 'deleted_at', 'label' => 'Deleted At', 'sortable' => true],
        ];
    }

    public function logs()
    {
        return GdprDeletionLog::query()
            ->when($this->search, fn (Builder $q) => $q->where('user_email', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn (Builder $q) => $q->where('deletion_type', $this->filterType))
            ->latest('deleted_at')
            ->paginate(20);
    }

    #[Layout('layouts::app')]
    public function render()
    {
        return view('livewire.admin.gdpr-deletion-logs', [
            'logs' => $this->logs(),
            'headers' => $this->headers(),
        ]);
    }
}
