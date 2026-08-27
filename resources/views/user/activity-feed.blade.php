<div>
    <x-ui::components.page-header
        :title="__('profile.activity_feed.title')"
        :description="__('profile.activity_feed.subtitle')"
    />

    <x-ts-card shadowless>
        @forelse ($activities as $activity)
            <div class="border-base-200 border-b py-3 last:border-b-0">
                <div class="flex items-start gap-3">
                    <x-ts-icon name="clock" class="text-base-content/40 mt-0.5 h-5 w-5" />
                    <div>
                        <p class="text-sm">{{ $activity->description }}</p>
                        <p class="text-base-content/50 text-xs">{{ $activity->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="py-8 text-center opacity-60">
                <x-ts-icon name="inbox" class="mx-auto mb-3 h-12 w-12" />
                <p>No activity found.</p>
            </div>
        @endforelse

        <div class="mt-4">{{ $activities->links() }}</div>
</div>
