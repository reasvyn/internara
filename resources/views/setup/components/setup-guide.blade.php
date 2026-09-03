<x-ts-modal wire="showGuide" :title="__('setup.guide.title')" separator blur size="lg">
    <div class="space-y-5">
        <p class="text-base-content/60 text-sm">{{ __('setup.guide.intro') }}</p>

        @foreach (range(1, 6) as $step)
            <div class="flex gap-4">
                <div class="bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold">
                    {{ $step }}
                </div>
                <div>
                    <h4 class="text-sm font-semibold">{{ __('setup.guide.step'.$step.'_title') }}</h4>
                    <p class="text-base-content/60 mt-1 text-xs leading-relaxed">
                        {{ __('setup.guide.step'.$step.'_desc') }}
                    </p>
                </div>
            </div>
        @endforeach

        <div class="border-base-content/10 mt-6 flex gap-4 border-t pt-4">
            <div class="bg-warning/10 text-warning mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full">
                <x-ts-icon name="light-bulb" class="size-4" />
            </div>
            <div>
                <h4 class="text-sm font-semibold">{{ __('setup.guide.tip_title') }}</h4>
                <p class="text-base-content/60 mt-1 text-xs leading-relaxed">{{ __('setup.guide.tip_desc') }}</p>
            </div>
        </div>
    </div>

    <x-slot:footer>
        <x-ts-button
            :text="__('common.actions.close')"
            wire:click="$set('showGuide', false)"
            color="slate"
            outline
            sm
        />
    </x-slot:footer>
</x-ts-modal>

<button
    type="button"
    wire:click="$set('showGuide', true)"
    class="bg-primary text-primary-content hover:bg-primary-focus fixed right-6 bottom-6 z-50 flex size-12 items-center justify-center rounded-full shadow-xl transition-all duration-200 hover:scale-110 active:scale-95"
    wire:key="guide-button"
    aria-label="{{ __('setup.guide.title') }}"
>
    <x-ts-icon name="question-mark-circle" class="size-6" />
</button>
