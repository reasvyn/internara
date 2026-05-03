<div class="p-12 md:p-24 text-center relative overflow-hidden rounded-[3rem]">
    <div class="absolute inset-0 bg-success/5 animate-pulse"></div>
    
    <div class="relative z-10">
        <div class="inline-flex items-center justify-center size-32 rounded-[2.5rem] bg-success text-white mb-12 shadow-2xl shadow-success/40 animate-bounce-slow">
            <x-mary-icon name="o-check-badge" class="size-16" />
        </div>
        <h2 class="text-5xl font-black tracking-tightest mb-6 uppercase">{{ __('setup.wizard.setup_complete') }}</h2>
        <p class="text-lg text-base-content/50 mb-16 max-w-lg mx-auto leading-relaxed font-semibold">
            {{ __('setup.wizard.ready_desc') }}
        </p>

        {{-- Admin Credentials Summary (High-End Card) --}}
        <div class="max-w-md mx-auto bg-base-100 rounded-[3rem] p-10 mb-16 shadow-2xl shadow-base-content/5 border border-success/20 relative">
            <div class="absolute -top-4 left-1/2 -translate-x-1/2 px-6 py-2 rounded-full bg-success text-white font-black uppercase text-[9px] tracking-[0.3em]">Access Granted</div>
            
            <div class="space-y-6">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-widest text-base-content/30">{{ __('setup.wizard.username') }}</span>
                    <span class="text-xl font-black text-primary tracking-tight">{{ $adminUsername }}</span>
                </div>
                <div class="divider opacity-5 my-0"></div>
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-black uppercase tracking-widest text-base-content/30">{{ __('setup.wizard.email') }}</span>
                    <span class="text-sm font-black">{{ $adminEmail }}</span>
                </div>
                
                <div class="bg-base-200/50 p-4 rounded-2xl mt-8">
                    <p class="text-[10px] font-bold text-base-content/40 leading-relaxed uppercase tracking-wider">
                        {{ __('setup.wizard.login_notice') }}
                    </p>
                </div>
            </div>
        </div>

        <div class="flex justify-center">
            <x-mary-button label="{{ __('setup.wizard.go_to_login') }}" icon-right="o-arrow-right" class="btn-success btn-wide h-16 rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] text-white shadow-2xl shadow-success/30" wire:click="finishSession" />
        </div>
    </div>
</div>
