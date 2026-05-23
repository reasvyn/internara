<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.welcome_back', ['name' => auth()->user()->name])" separator />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <x-mary-card :title="__('dashboard.recent_activity')" separator>
                @forelse($activities as $activity)
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
                    <div class="py-8 text-center">
                        <x-mary-icon name="o-inbox" class="size-8 text-base-content/20 mx-auto mb-2" />
                        <p class="text-sm text-base-content/40">{{ __('dashboard.no_activity') }}</p>
                    </div>
                @endforelse
            </x-mary-card>
        </div>

        <div class="space-y-6">
            <x-mary-card :title="__('dashboard.your_profile')" separator>
                <div class="flex items-center gap-4">
                    <x-mary-avatar
                        :image="auth()->user()->getFirstMediaUrl('avatar', 'thumb') ?: null"
                        placeholder="{{ auth()->user()->initials() }}"
                        class="!w-16 !h-16"
                    />
                    <div>
                        <div class="font-bold">{{ auth()->user()->name }}</div>
                        <div class="text-sm text-base-content/50">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <x-slot:actions>
                    <x-mary-button :label="__('dashboard.edit_profile')" icon="o-pencil" link="{{ route('profile') }}" class="btn-sm" wire:navigate />
                </x-slot:actions>
            </x-mary-card>

            <x-mary-card :title="__('dashboard.quick_links')" separator>
                <div class="space-y-2">
                    <a href="{{ route('profile') }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200/50 transition-colors">
                        <x-mary-icon name="o-user" class="size-4 text-base-content/40" />
                        <span class="text-sm">{{ __('dashboard.edit_profile') }}</span>
                    </a>
                    <a href="{{ route('profile.recovery') }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200/50 transition-colors">
                        <x-mary-icon name="o-key" class="size-4 text-base-content/40" />
                        <span class="text-sm">{{ __('profile.recovery.title') }}</span>
                    </a>
                    <a href="{{ route('notifications') }}" wire:navigate class="flex items-center gap-3 p-3 rounded-lg hover:bg-base-200/50 transition-colors">
                        <x-mary-icon name="o-bell" class="size-4 text-base-content/40" />
                        <span class="text-sm">{{ __('dashboard.notifications') }}</span>
                    </a>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
