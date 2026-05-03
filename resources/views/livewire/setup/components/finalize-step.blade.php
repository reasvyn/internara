<div class="p-8 md:p-12">
    <div class="flex items-center gap-6 mb-12">
        <div class="size-16 rounded-[2rem] bg-primary/5 border border-primary/10 flex items-center justify-center text-primary shadow-inner">
            <x-mary-icon name="o-clipboard-document-check" class="size-8" />
        </div>
        <div>
            <h2 class="text-3xl font-black tracking-tighter">{{ __('setup.wizard.finalize') }}</h2>
            <p class="text-[11px] text-base-content/40 uppercase font-black tracking-[0.2em] mt-1">{{ __('setup.wizard.finalize_subtitle') }}</p>
        </div>
    </div>

    <div class="space-y-6 mb-12">
        <label class="flex items-center gap-6 p-8 bg-base-200/50 hover:bg-base-200 rounded-[2.5rem] cursor-pointer transition-all border border-transparent hover:border-primary/20 group">
            <input type="checkbox" wire:model.live="dataVerified" class="checkbox checkbox-primary checkbox-lg rounded-xl" />
            <div>
                <span class="text-lg font-black tracking-tight block mb-1 group-hover:text-primary transition-colors">{{ __('setup.wizard.data_verified') }}</span>
                <p class="text-xs font-medium text-base-content/40 uppercase tracking-widest leading-relaxed">I have double-checked all provided information for accuracy.</p>
            </div>
        </label>
        <label class="flex items-center gap-6 p-8 bg-base-200/50 hover:bg-base-200 rounded-[2.5rem] cursor-pointer transition-all border border-transparent hover:border-primary/20 group">
            <input type="checkbox" wire:model.live="securityAware" class="checkbox checkbox-primary checkbox-lg rounded-xl" />
            <div>
                <span class="text-lg font-black tracking-tight block mb-1 group-hover:text-primary transition-colors">{{ __('setup.wizard.security_aware') }}</span>
                <p class="text-xs font-medium text-base-content/40 uppercase tracking-widest leading-relaxed">I understand that these settings are critical for system operation.</p>
            </div>
        </label>
    </div>

    <div class="mt-16 flex justify-between items-center pt-8 border-t border-base-content/5">
        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-2xl font-black uppercase tracking-widest text-[10px] px-8" />
        <x-mary-button label="{{ __('setup.wizard.finish_setup') }}" icon-right="o-check" class="btn-primary rounded-[2rem] font-black uppercase tracking-[0.2em] text-[10px] px-12 h-14 shadow-2xl shadow-primary/30" wire:click="finish" spinner="finish" />
    </div>
</div>
