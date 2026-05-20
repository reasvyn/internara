<div>
    <x-mary-header title="Activity Feed" subtitle="Recent user activity" separator />

    <x-mary-card>
        @forelse($activities as $activity)
            <div class="py-3 border-b border-base-200 last:border-b-0">
                <div class="flex items-start gap-3">
                    <x-mary-icon name="o-clock" class="text-base-content/40 w-5 h-5 mt-0.5" />
                    <div>
                        <p class="text-sm">{{ $activity->description }}</p>
                        <p class="text-xs text-base-content/50">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-8 opacity-60">
                <x-mary-icon name="o-inbox" class="w-12 h-12 mx-auto mb-3" />
                <p>No activity found.</p>
            </div>
        @endforelse

        <div class="mt-4">
            {{ $activities->links() }}
        </div>
    </x-mary-card>
</div>
