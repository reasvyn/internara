<div class="p-8 md:p-12">
    <div class="flex items-center gap-6 mb-12">
        <div class="size-16 rounded-[2rem] bg-primary/5 border border-primary/10 flex items-center justify-center text-primary shadow-inner">
            <x-mary-icon name="o-academic-cap" class="size-8" />
        </div>
        <div>
            <h2 class="text-3xl font-black tracking-tighter">{{ __('setup.wizard.school_info') }}</h2>
            <p class="text-[11px] text-base-content/40 uppercase font-black tracking-[0.2em] mt-1">{{ __('setup.wizard.school_subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-10">
        <x-mary-input label="{{ __('setup.wizard.school_name') }}" wire:model.live="schoolName" class="rounded-2xl border-base-200 focus:border-primary/30 transition-all font-medium py-3" />
        <x-mary-input label="{{ __('setup.wizard.school_code') }}" wire:model.live="schoolCode" class="rounded-2xl border-base-200 focus:border-primary/30 transition-all font-medium py-3" />
        <div class="md:col-span-2">
            <x-mary-textarea label="{{ __('setup.wizard.school_address') }}" wire:model.live="schoolAddress" rows="4" class="rounded-2xl border-base-200 focus:border-primary/30 transition-all font-medium" />
        </div>
        <x-mary-input label="{{ __('setup.wizard.school_email') }}" type="email" wire:model.live="schoolEmail" class="rounded-2xl border-base-200 focus:border-primary/30 transition-all font-medium py-3" />
        <x-mary-input label="{{ __('setup.wizard.school_phone') }}" wire:model.live="schoolPhone" class="rounded-2xl border-base-200 focus:border-primary/30 transition-all font-medium py-3" />
        <x-mary-input label="{{ __('setup.wizard.school_website') }}" type="url" wire:model.live="schoolWebsite" class="rounded-2xl border-base-200 focus:border-primary/30 transition-all font-medium py-3" />
        <x-mary-input label="{{ __('setup.wizard.principal_name') }}" wire:model.live="principalName" class="rounded-2xl border-base-200 focus:border-primary/30 transition-all font-medium py-3" />
    </div>
    
    <div class="mt-16 flex justify-between items-center pt-8 border-t border-base-content/5">
        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-2xl font-black uppercase tracking-widest text-[10px] px-8" />
        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-2xl font-black uppercase tracking-widest text-[10px] px-10 shadow-xl shadow-primary/20" wire:click="nextStep" spinner="nextStep" />
    </div>
</div>
