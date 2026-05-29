<x-shared::widgets.profile-summary :showEdit="true" />

<x-mary-card :title="__('dashboard.recent_activity')" separator>
    @forelse($this->getRecentActivities() as $activity)
        <div class="flex items-start gap-4 py-3 border-b last:border-0 border-base-content/10">
            <div class="mt-1">
                <x-mary-icon name="o-bolt" class="size-4 text-base-content/30" />
            </div>
            <div>
                <div class="text-sm font-medium">{{ __("activity.{$activity->description}") !== "activity.{$activity->description}" ? __("activity.{$activity->description}") : str($activity->description)->headline() }}</div>
                <div class="text-xs text-base-content/40">{{ $activity->created_at->locale(app()->getLocale())->diffForHumans() }}</div>
            </div>
        </div>
    @empty
        <x-shared::widgets.empty-state icon="o-inbox" :title="__('dashboard.no_activity')" />
    @endforelse
</x-mary-card>

<x-mary-card :title="__('dashboard.quick_links')" separator>
    <div class="space-y-1">
        <x-shared::widgets.quick-link :label="__('dashboard.edit_profile')" icon="o-user" link="{{ route('profile') }}" />
        <x-shared::widgets.quick-link :label="__('profile.recovery.title')" icon="o-key" link="{{ route('profile.recovery') }}" />
        <x-shared::widgets.quick-link :label="__('dashboard.notifications')" icon="o-bell" link="{{ route('notifications') }}" />
        @if(auth()->user()?->hasRole('super_admin'))
            <x-shared::widgets.quick-link :label="__('dashboard.system_settings')" icon="o-cog-6-tooth" link="{{ route('admin.settings') }}" />
        @endif
    </div>
</x-mary-card>
