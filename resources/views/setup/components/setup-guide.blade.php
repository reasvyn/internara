<x-mary-modal wire:model="showGuide" :title="__('setup.guide.title')" separator class="backdrop-blur-sm" size="lg">
    <div class="space-y-5">
        <p class="text-sm text-base-content/60">{{ __('setup.guide.intro') }}</p>

        @foreach(range(1, 6) as $step)
            <div class="flex gap-4">
                <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary font-bold text-sm shrink-0 mt-0.5">
                    {{ $step }}
                </div>
                <div>
                    <h4 class="font-semibold text-sm">{{ __('setup.guide.step'.$step.'_title') }}</h4>
                    <p class="text-xs text-base-content/60 mt-1 leading-relaxed">{{ __('setup.guide.step'.$step.'_desc') }}</p>
                </div>
            </div>
        @endforeach

        <div class="flex gap-4 mt-6 pt-4 border-t border-base-content/10">
            <div class="flex items-center justify-center size-8 rounded-full bg-warning/10 text-warning shrink-0 mt-0.5">
                <x-mary-icon name="o-light-bulb" class="size-4" />
            </div>
            <div>
                <h4 class="font-semibold text-sm">{{ __('setup.guide.tip_title') }}</h4>
                <p class="text-xs text-base-content/60 mt-1 leading-relaxed">{{ __('setup.guide.tip_desc') }}</p>
            </div>
        </div>
    </div>

    <x-slot:actions>
        <x-mary-button :label="__('common.actions.close')" wire:click="$set('showGuide', false)" class="btn-ghost btn-sm" />
    </x-slot:actions>
</x-mary-modal>

<button
    type="button"
    wire:click="$set('showGuide', true)"
    class="fixed bottom-6 right-6 z-50 flex items-center justify-center size-12 rounded-full shadow-xl bg-primary text-primary-content hover:bg-primary-focus transition-all duration-200 hover:scale-110 active:scale-95"
    wire:key="guide-button"
    aria-label="{{ __('setup.guide.title') }}"
>
    <x-mary-icon name="o-question-mark-circle" class="size-6" />
</button>
