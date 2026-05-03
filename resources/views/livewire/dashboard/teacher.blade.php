<div class="animate-in fade-in slide-in-from-bottom-8 duration-1000">
    <div class="flex items-center justify-between mb-8">
        <div>
            <h2 class="text-3xl font-black tracking-tightest text-base-content">{{ __('dashboard.title') ?? 'Teacher Dashboard' }}</h2>
            <p class="text-[10px] font-black uppercase tracking-[0.3em] text-base-content/40 mt-2">Welcome back, {{ auth()->user()->name }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <x-mary-stat 
            title="Supervised Students"
            value="0" 
            icon="o-users" 
            class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 rounded-[2rem] hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
            color="text-primary" 
        />
        <x-mary-stat 
            title="Pending Journals"
            value="0" 
            icon="o-book-open" 
            class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 rounded-[2rem] hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
            color="text-warning" 
        />
        <x-mary-stat 
            title="Active Companies"
            value="0" 
            icon="o-building-office" 
            class="!bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5 rounded-[2rem] hover:-translate-y-1 hover:shadow-xl transition-all duration-300"
            color="text-secondary" 
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- Main Task Area --}}
        <div class="lg:col-span-2 space-y-8">
            <x-mary-card shadow class="card-enterprise !bg-base-100 shadow-2xl shadow-base-content/5 border border-base-content/5">
                <x-slot:title>
                    <span class="text-xl font-black tracking-tightest">Recent Student Journals</span>
                </x-slot:title>
                <div class="flex flex-col items-center justify-center py-16 opacity-20">
                    <div class="size-20 rounded-[2.5rem] bg-base-200 flex items-center justify-center mb-4 shadow-inner">
                        <x-mary-icon name="o-clipboard-document-check" class="size-10" />
                    </div>
                    <span class="font-black uppercase tracking-[0.3em] text-[9px]">No journals pending review</span>
                </div>
            </x-mary-card>
        </div>

        {{-- Right Column --}}
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

            <div class="grid grid-cols-1 gap-6">
                <x-mary-button 
                    label="Guidance Logs" 
                    icon="o-clipboard-check" 
                    class="btn-primary h-20 rounded-[1.5rem] font-black uppercase tracking-[0.1em] text-[10px] shadow-xl shadow-primary/20 transition-transform duration-300 hover:scale-[1.02]" 
                    link="{{ route('supervision.logs') }}" 
                    wire:navigate
                />
            </div>
        </div>
    </div>
</div>
