<div class="p-8 md:p-12">
    <div class="flex items-center gap-6 mb-12">
        <div class="size-16 rounded-[2rem] bg-primary/5 border border-primary/10 flex items-center justify-center text-primary shadow-inner">
            <x-mary-icon name="o-briefcase" class="size-8" />
        </div>
        <div>
            <h2 class="text-3xl font-black tracking-tighter">{{ __('setup.wizard.internship') }}</h2>
            <p class="text-[11px] text-base-content/40 uppercase font-black tracking-[0.2em] mt-1">{{ __('setup.wizard.internship_subtitle') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-10">
        <x-mary-input label="{{ __('setup.wizard.program_name') }}" wire:model.live="internshipName" class="rounded-2xl py-3" />
        <x-mary-textarea label="{{ __('setup.wizard.program_description') }}" wire:model.live="internshipDescription" rows="3" class="rounded-2xl" />

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <x-mary-input label="{{ __('setup.wizard.start_date') }}" type="date" wire:model.live="startDate" class="rounded-2xl py-3" />
            <x-mary-input label="{{ __('setup.wizard.end_date') }}" type="date" wire:model.live="endDate" class="rounded-2xl py-3" />
        </div>
    </div>
    
    <div class="mt-16 flex justify-between items-center pt-8 border-t border-base-content/5">
        <x-mary-button label="{{ __('setup.wizard.back') }}" wire:click="prevStep" class="btn-ghost rounded-2xl font-black uppercase tracking-widest text-[10px] px-8" />
        <x-mary-button label="{{ __('setup.wizard.next_step') }}" icon-right="o-arrow-right" class="btn-primary rounded-2xl font-black uppercase tracking-widest text-[10px] px-10 shadow-xl shadow-primary/20" wire:click="nextStep" spinner="nextStep" />
    </div>
</div>
