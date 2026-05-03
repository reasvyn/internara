<div class="p-8 md:p-12">
    <div class="flex items-center gap-6 mb-12">
        <div class="size-16 rounded-[2rem] bg-primary/5 border border-primary/10 flex items-center justify-center text-primary shadow-inner">
            <x-mary-icon name="o-user-circle" class="size-8" />
        </div>
        <div>
            <h2 class="text-3xl font-black tracking-tighter">{{ __('setup.wizard.admin_account') }}</h2>
            <p class="text-[11px] text-base-content/40 uppercase font-black tracking-[0.2em] mt-1">{{ __('setup.wizard.admin_subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-10">
        <x-mary-input label="{{ __('setup.wizard.full_name') }}" wire:model.live="adminName" class="rounded-2xl py-3" />
        <x-mary-input label="{{ __('setup.wizard.email_address') }}" type="email" wire:model.live="adminEmail" class="rounded-2xl py-3" />
        
        <div class="md:col-span-2 bg-base-200/50 p-8 rounded-[2.5rem] border border-base-content/5 relative overflow-hidden group">
            <div class="absolute -right-12 -top-12 size-48 rounded-full bg-primary/5 blur-3xl transition-transform group-hover:scale-125 duration-1000"></div>
            <div class="flex items-center gap-8 relative z-10">
                <div class="shrink-0 size-20 rounded-3xl bg-primary text-white flex items-center justify-center shadow-2xl shadow-primary/30">
                    <x-mary-icon name="o-finger-print" class="size-10" />
                </div>
                <div class="flex-1">
                    <label class="text-[10px] font-black uppercase tracking-[0.3em] text-primary block mb-3">{{ __('setup.wizard.username') }}</label>
                    <div class="flex items-center gap-4">
                        <span class="text-4xl font-black tracking-tightest">{{ $adminUsername }}</span>
                        <span class="px-3 py-1 rounded-full bg-primary/10 text-primary font-black uppercase text-[9px] tracking-widest">{{ __('setup.wizard.generated') }}</span>
                    </div>
                    <p class="text-xs font-medium text-base-content/40 mt-4 leading-relaxed max-w-lg">
                        {{ __('setup.wizard.username_notice') }}
                    </p>
                </div>
            </div>
        </div>

        <x-mary-input label="{{ __('setup.wizard.password') }}" type="password" wire:model.live="adminPassword" class="rounded-2xl py-3" />
        <x-mary-input label="{{ __('setup.wizard.confirm_password') }}" type="password" wire:model.live="adminPassword_confirmation" class="rounded-2xl py-3" />
    </div>
    
    <div class="mt-16 flex justify-between items-center pt-8 border-t border-base-content/5">
        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-2xl font-black uppercase tracking-widest text-[10px] px-8" />
        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-2xl font-black uppercase tracking-widest text-[10px] px-10 shadow-xl shadow-primary/20" wire:click="nextStep" spinner="nextStep" />
    </div>
</div>
