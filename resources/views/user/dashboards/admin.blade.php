<div>
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-xl font-bold">{{ __('dashboard.title') }}</h2>
            <p class="text-sm text-base-content/50">{{ __('dashboard.subtitle', ['name' => auth()->user()->name]) }}</p>
        </div>
        <x-mary-button :label="__('setting.title')" icon="o-cog-6-tooth" link="{{ route('admin.settings') }}" class="btn-ghost btn-sm" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-mary-stat
            :title="__('dashboard.stats.total_students')"
            :value="$stats['students']"
            icon="o-users"
            class="bg-base-100 border border-base-content/10 rounded-xl"
            color="text-primary"
        />
        <x-mary-stat
            :title="__('dashboard.stats.instructors')"
            :value="$stats['teachers']"
            icon="o-academic-cap"
            class="bg-base-100 border border-base-content/10 rounded-xl"
            color="text-secondary"
        />
        <x-mary-stat
            :title="__('dashboard.stats.departments')"
            :value="$stats['departments']"
            icon="o-building-library"
            class="bg-base-100 border border-base-content/10 rounded-xl"
            color="text-accent"
        />
        <x-mary-stat
            :title="__('dashboard.stats.active_programs')"
            :value="$stats['internships']"
            icon="o-briefcase"
            class="bg-base-100 border border-base-content/10 rounded-xl"
            color="text-info"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <span class="font-semibold">{{ __('dashboard.readiness.title') }}</span>
                </x-slot:title>
                <x-slot:subtitle>
                    <span class="text-xs text-base-content/50">{{ __('dashboard.readiness.subtitle') }}</span>
                </x-slot:subtitle>

                <div class="space-y-3 mt-4">
                    @foreach($readiness as $key => $status)
                        <div class="flex items-center justify-between px-4 py-3 rounded-lg bg-base-200/30 border border-base-content/10">
                            <div class="flex items-center gap-3">
                                <x-mary-icon :name="$status['passed'] ? 'o-check-circle' : 'o-x-circle'" class="size-5 shrink-0" :class="$status['passed'] ? 'text-success' : 'text-error'" />
                                <span class="text-sm">{{ $status['label'] }}</span>
                            </div>
                            <x-mary-badge
                                :label="$status['passed'] ? __('common.status.completed') : __('common.status.pending')"
                                :class="$status['passed'] ? 'badge-success badge-sm' : 'badge-error badge-sm'"
                            />
                        </div>
                    @endforeach
                </div>
            </x-mary-card>
        </div>

        <div class="flex flex-col gap-4">
            <x-mary-card class="bg-base-100 border border-base-content/10 text-center">
                <div class="flex flex-col items-center py-4">
                    <x-mary-avatar placeholder="{{ auth()->user()->initials() }}" class="!w-16 !h-16 mb-3" />
                    <h3 class="font-semibold">{{ auth()->user()->name }}</h3>
                    <p class="text-xs text-base-content/50 mt-0.5">{{ auth()->user()->getRoleNames()->first() }}</p>
                    <div class="w-full mt-4">
                        <x-mary-button :label="__('dashboard.profile.edit')" icon="o-user" class="btn-ghost btn-sm w-full" link="{{ route('profile') }}" />
                    </div>
                </div>
            </x-mary-card>

            <x-mary-card class="bg-gradient-to-br from-primary to-primary/80 text-white border-none">
                <div class="py-2">
                    <h4 class="font-semibold mb-1">{{ __('dashboard.help_title') }}</h4>
                    <p class="text-xs text-white/80 mb-4">{{ __('dashboard.help_desc', ['app' => config('app.name')]) }}</p>
                    <x-mary-button :label="__('dashboard.read_docs')" link="https://github.com/reasvyn/internara" class="btn-sm bg-white text-primary border-none hover:bg-white/90" />
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
