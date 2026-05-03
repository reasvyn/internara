<div class="p-8 md:p-12">
    <div class="flex items-center gap-6 mb-12">
        <div class="size-16 rounded-[2rem] bg-primary/5 border border-primary/10 flex items-center justify-center text-primary shadow-inner">
            <x-mary-icon name="o-rectangle-group" class="size-8" />
        </div>
        <div>
            <h2 class="text-3xl font-black tracking-tighter">{{ __('setup.wizard.department') }}</h2>
            <p class="text-[11px] text-base-content/40 uppercase font-black tracking-[0.2em] mt-1">{{ __('setup.wizard.department_subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-10">
        <div class="bg-base-200/50 p-6 rounded-3xl border-l-4 border-primary">
            <p class="text-sm font-semibold text-base-content/70 leading-relaxed">
                {{ __('setup.wizard.department_desc') }}
            </p>
        </div>
        
        <x-mary-input label="{{ __('setup.wizard.department_name') }}" wire:model.live="departmentName" class="rounded-2xl py-3" />
        <x-mary-textarea label="{{ __('setup.wizard.department_description') }}" wire:model.live="departmentDescription" rows="4" class="rounded-2xl" />
    </div>
    
    <div class="mt-16 flex justify-between items-center pt-8 border-t border-base-content/5">
        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-2xl font-black uppercase tracking-widest text-[10px] px-8" />
        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-2xl font-black uppercase tracking-widest text-[10px] px-10 shadow-xl shadow-primary/20" wire:click="nextStep" spinner="nextStep" />
    </div>
</div>
