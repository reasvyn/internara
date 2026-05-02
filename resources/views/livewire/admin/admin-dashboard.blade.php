<div class="p-8">
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.subtitle')" separator progress-indicator>
        <x-slot:actions>
            <x-mary-button :label="__('setting.title')" icon="o-cog-6-tooth" link="{{ route('admin.settings') }}" class="btn-ghost btn-sm text-primary font-black uppercase tracking-widest" />
        </x-slot:actions>
    </x-mary-header>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-mary-stat 
            :title="__('dashboard.stats.total_students')"
            :value="$stats['students']" 
            icon="o-users" 
            class="stat-enterprise" 
        />
        <x-mary-stat 
            :title="__('dashboard.stats.instructors')"
            :value="$stats['teachers']" 
            icon="o-academic-cap" 
            class="stat-enterprise" 
        />
        <x-mary-stat 
            :title="__('dashboard.stats.departments')"
            :value="$stats['departments']" 
            icon="o-building-library" 
            class="stat-enterprise" 
        />
        <x-mary-stat 
            :title="__('dashboard.stats.active_programs')"
            :value="$stats['internships']" 
            icon="o-briefcase" 
            class="stat-enterprise" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- System Readiness Card --}}
        <div class="lg:col-span-2">
            <x-mary-card :title="__('dashboard.cards.system_readiness')" :subtitle="__('dashboard.cards.readiness_subtitle')" class="card-enterprise">
                <div class="space-y-4 mt-4">
                    @foreach($readiness as $key => $status)
                        <div class="flex items-center justify-between p-4 rounded-2xl bg-base-200/50 transition-colors hover:bg-base-200">
                            <div class="flex items-center gap-4">
                                <div @class([
                                    'size-10 rounded-xl flex items-center justify-center',
                                    'bg-success/10 text-success' => $status['passed'],
                                    'bg-error/10 text-error' => !$status['passed']
                                ])>
                                    <x-mary-icon :name="$status['passed'] ? 'o-check-circle' : 'o-x-circle'" class="size-6" />
                                </div>
                                <span class="font-black text-sm uppercase tracking-tight">{{ $status['label'] }}</span>
                            </div>
                            <x-mary-badge :label="$status['passed'] ? __('common.status.completed') : __('common.status.pending')" :class="$status['passed'] ? 'badge-success font-black text-[10px]' : 'badge-error font-black text-[10px]'" />
                        </div>
                    @endforeach
                </div>
            </x-mary-card>
        </div>

        {{-- Profile/Info Card --}}
        <div class="flex flex-col gap-8">
            <x-mary-card :title="__('dashboard.cards.admin_info')" class="card-enterprise bg-gradient-to-br from-base-100 to-base-200/50">
                <div class="flex flex-col items-center text-center py-4">
                    <x-mary-avatar :title="auth()->user()->name" class="!w-24 !h-24 rounded-3xl mb-4 border-4 border-white shadow-2xl transition-transform hover:scale-105 duration-500" />
                    <h3 class="text-xl font-black tracking-tighter">{{ auth()->user()->name }}</h3>
                    <p class="text-[10px] font-black opacity-30 uppercase tracking-[0.2em] mt-1">{{ auth()->user()->getRoleNames()->first() }}</p>
                    
                    <div class="w-full mt-8">
                        <x-mary-button :label="__('common.actions.edit')" icon="o-user-circle" class="btn-sm w-full rounded-xl bg-base-200 border-none font-bold uppercase tracking-widest text-xs" link="{{ route('profile') }}" />
                    </div>
                </div>
            </x-mary-card>

            <div class="rounded-[2.5rem] bg-primary p-8 text-white relative overflow-hidden shadow-2xl shadow-primary/20 transition-all duration-500 hover:scale-[1.02] group">
                <div class="relative z-10">
                    <h4 class="text-xl font-black leading-tight mb-2 uppercase tracking-tighter">Need Help?</h4>
                    <p class="text-sm opacity-80 mb-6 font-medium leading-relaxed">Explore the documentation to master Internara features and boost productivity.</p>
                    <x-mary-button label="Read Docs" class="btn-sm bg-white text-primary border-none rounded-xl font-black uppercase tracking-widest text-[10px] px-6" />
                </div>
                <x-mary-icon name="o-book-open" class="absolute -right-8 -bottom-8 size-40 opacity-10 rotate-12 transition-transform group-hover:scale-110 duration-700" />
            </div>
        </div>
    </div>
</div>
