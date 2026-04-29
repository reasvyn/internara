<?php

declare(strict_types=1);

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RecentActivityList extends Component
{
    /**
     * Get recent activities for the current user.
     */
    #[Computed]
    public function activities()
    {
        return AuditLog::where('user_id', auth()->id())
            ->latest()
            ->take(10)
            ->get();
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            @forelse($this->activities as $activity)
                <div class="flex items-start gap-4 py-3 border-b last:border-0 border-base-200">
                    <div class="mt-1">
                        <x-mary-icon name="o-bolt" class="size-4 opacity-50" />
                    </div>
                    <div>
                        <div class="text-sm font-medium">{{ str($activity->action)->headline() }}</div>
                        <div class="text-xs opacity-50">{{ $activity->created_at->diffForHumans() }} • {{ $activity->ip_address }}</div>
                    </div>
                </div>
            @empty
                <div class="py-4 text-center opacity-50">No recent activity found.</div>
            @endforelse
        </div>
        HTML;
    }
}
