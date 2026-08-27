<div>
    <x-ui::components.page-header
        :title="__('dashboard.title')"
        :description="__('dashboard.welcome_back', ['name' => auth()->user()->name])"
    />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @if (isset($roleContent))
                {{ $roleContent }}
            @else
                <x-ts-card shadowless :header="__('dashboard.recent_activity')">
                    @forelse ($this->getRecentActivities() as $activity)
                        <div class="border-base-content/10 flex items-start gap-4 border-b py-3 last:border-0">
                            <div class="mt-1">
                                <x-ts-icon name="bolt" class="text-base-content/30 size-4" />
                            </div>
                            <div>
                                <div class="text-sm font-medium">{{ str($activity->description)->headline() }}</div>
                                <div class="text-base-content/40 text-xs">
                                    {{ $activity->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    @empty
                        <x-ui::widgets.empty-state icon="inbox" :title="__('dashboard.no_activity')" />
                    @endforelse

            @endif
        </div>

        <div class="space-y-6">
            <x-ui::widgets.profile-summary :showEdit="true" />

            <x-ts-card shadowless :header="__('dashboard.recent_activity')">
                @forelse ($this->getRecentActivities() as $activity)
                    <div class="border-base-content/10 flex items-start gap-4 border-b py-3 last:border-0">
                        <div class="mt-1">
                            <x-ts-icon name="bolt" class="text-base-content/30 size-4" />
                        </div>
                        <div>
                            <div class="text-sm font-medium">
                                {{ __("activity.{$activity->description}") !== "activity.{$activity->description}" ? __("activity.{$activity->description}") : str($activity->description)->headline() }}
                            </div>
                            <div class="text-base-content/40 text-xs">
                                {{ $activity->created_at->locale(app()->getLocale())->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                @empty
                    <x-ui::widgets.empty-state icon="inbox" :title="__('dashboard.no_activity')" />
                @endforelse

                <x-ts-card shadowless :header="__('dashboard.quick_links')">
                    <div class="space-y-1">
                        <x-ui::widgets.quick-link
                            :label="__('dashboard.edit_profile')"
                            icon="user"
                            link="{{ route('profile') }}"
                        />
                        <x-ui::widgets.quick-link
                            :label="__('profile.recovery.title')"
                            icon="key"
                            link="{{ route('profile.recovery') }}"
                        />
                        <x-ui::widgets.quick-link
                            :label="__('dashboard.notifications')"
                            icon="bell"
                            link="{{ route('notifications') }}"
                        />
                        @if (auth()->user()?->hasRole('super_admin'))
                            <x-ui::widgets.quick-link
                                :label="__('dashboard.system_settings')"
                                icon="cog-6-tooth"
                                link="{{ route('admin.settings') }}"
                            />
                        @endif
                    </div>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
