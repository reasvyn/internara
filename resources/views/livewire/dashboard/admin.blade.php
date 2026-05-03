<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">{{ __('dashboard.title') }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">{{ __('dashboard.subtitle') }}</p>
        </div>
        <x-mary-button :label="__('setting.title')" icon="o-cog-6-tooth" link="{{ route('admin.settings') }}" class="btn-ghost rounded-[2rem] text-primary font-black uppercase tracking-widest text-[10px] px-6 h-12" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <x-mary-stat 
            :title="__('dashboard.stats.total_students')"
            :value="$stats['students']" 
            icon="o-users" 
            class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 rounded-[2rem] hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
            color="text-primary" 
        />
        <x-mary-stat 
            :title="__('dashboard.stats.instructors')"
            :value="$stats['teachers']" 
            icon="o-academic-cap" 
            class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 rounded-[2rem] hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
            color="text-secondary" 
        />
        <x-mary-stat 
            :title="__('dashboard.stats.departments')"
            :value="$stats['departments']" 
            icon="o-building-library" 
            class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 rounded-[2rem] hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
            color="text-accent" 
        />
        <x-mary-stat 
            :title="__('dashboard.stats.active_programs')"
            :value="$stats['internships']" 
            icon="o-briefcase" 
            class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 rounded-[2rem] hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
            color="text-info" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- System Readiness Card --}}
        <div class="lg:col-span-2">
            <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 p-2">
                <x-slot:title>
                    <span class="text-xl font-black tracking-tightest">{{ __('dashboard.cards.system_readiness') }}</span>
                </x-slot:title>
                <x-slot:subtitle>
                    <span class="text-[9px] font-black uppercase tracking-[0.2em] text-base-content/40">{{ __('dashboard.cards.readiness_subtitle') }}</span>
                </x-slot:subtitle>

                <div class="space-y-4 mt-6">
                    @foreach($readiness as $key => $status)
                        <div class="flex items-center justify-between p-4 rounded-[1.5rem] bg-base-200/50 transition-colors hover:bg-base-200 border border-base-content/5 group">
                            <div class="flex items-center gap-5">
                                <div @class([
                                    'size-12 rounded-2xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-110 duration-300 shadow-inner',
                                    'bg-success/10 text-success border border-success/10' => $status['passed'],
                                    'bg-error/10 text-error border border-error/10' => !$status['passed']
                                ])>
                                    <x-mary-icon :name="$status['passed'] ? 'o-check-circle' : 'o-x-circle'" class="size-6" />
                                </div>
                                <span class="font-black text-sm tracking-tight text-base-content group-hover:text-primary transition-colors">{{ $status['label'] }}</span>
                            </div>
                            <x-mary-badge :label="$status['passed'] ? __('common.status.completed') : __('common.status.pending')" :class="$status['passed'] ? 'badge-success font-black text-[9px] uppercase tracking-widest px-4 py-3' : 'badge-error font-black text-[9px] uppercase tracking-widest px-4 py-3'" />
                        </div>
                    @endforeach
                </div>
            </x-mary-card>
        </div>

        {{-- Profile/Info Card --}}
        <div class="flex flex-col gap-8">
            <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 text-center relative overflow-hidden">
                <div class="absolute top-0 right-0 size-32 bg-primary/5 rounded-full blur-2xl pointer-events-none"></div>
                <div class="flex flex-col items-center py-4 relative z-10">
                    <x-mary-avatar :title="auth()->user()->name" class="!w-24 !h-24 rounded-[2rem] mb-6 border-4 border-base-100 shadow-xl transition-transform hover:scale-105 duration-500" />
                    <h3 class="text-2xl font-black tracking-tightest">{{ auth()->user()->name }}</h3>
                    <p class="text-[10px] font-black text-primary uppercase tracking-[0.3em] mt-2">{{ auth()->user()->getRoleNames()->first() }}</p>
                    
                    <div class="w-full mt-8">
                        <x-mary-button :label="__('common.actions.edit')" icon="o-user-circle" class="btn-ghost bg-base-200/50 hover:bg-primary hover:text-white w-full h-12 rounded-[1.5rem] font-black uppercase tracking-widest text-[10px] transition-all" link="{{ route('profile') }}" />
                    </div>
                </div>
            </x-mary-card>

            <div class="rounded-[2rem] bg-gradient-to-br from-primary to-primary/80 p-8 text-white relative overflow-hidden shadow-2xl shadow-primary/30 transition-transform duration-500 hover:scale-[1.02] group">
                <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10 mix-blend-overlay"></div>
                <div class="relative z-10">
                    <h4 class="text-2xl font-black leading-tight mb-2 tracking-tighter">Need Help?</h4>
                    <p class="text-xs opacity-90 mb-8 font-medium leading-relaxed">Explore the documentation to master {{ config('app.name') }} features and boost productivity.</p>
                    <x-mary-button label="Read Docs" class="btn-sm bg-white text-primary border-none rounded-xl font-black uppercase tracking-widest text-[10px] px-6 shadow-lg shadow-black/10 hover:scale-105 transition-transform" />
                </div>
                <x-mary-icon name="o-book-open" class="absolute -right-6 -bottom-6 size-48 opacity-20 rotate-12 transition-transform group-hover:scale-110 group-hover:-rotate-12 duration-700 pointer-events-none" />
            </div>
        </div>
    </div>
</div>
