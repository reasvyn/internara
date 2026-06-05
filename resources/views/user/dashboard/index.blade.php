<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.welcome_back', ['name' => auth()->user()->name])" separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if(isset($roleContent))
                {{ $roleContent }}
            @else
                <x-mary-card :title="__('dashboard.recent_activity')" separator>
                    @forelse($this->getRecentActivities() as $activity)
                        <div class="flex items-start gap-4 py-3 border-b last:border-0 border-base-content/10">
                            <div class="mt-1">
                                <x-mary-icon name="o-bolt" class="size-4 text-base-content/30" />
                            </div>
                            <div>
                                <div class="text-sm font-medium">{{ str($activity->description)->headline() }}</div>
                                <div class="text-xs text-base-content/40">{{ $activity->created_at->diffForHumans() }}</div>
                            </div>
                        </div>
                    @empty
                        <x-core::widgets.empty-state icon="o-inbox" :title="__('dashboard.no_activity')" />
                    @endforelse
                </x-mary-card>
            @endif
        </div>

        <div class="space-y-6">
            @include('user.dashboard._sidebar')
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
