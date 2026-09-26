<x-ts-modal wire="showGuide" :title="__('setup.guide.title')" separator blur size="lg">
    <div class="space-y-4">
        <p class="text-base-content/60 text-sm leading-relaxed">{{ __('setup.guide.intro') }}</p>

        <div class="divide-base-content/10 border-base-content/10 bg-base-200/30 divide-y overflow-hidden rounded-2xl border">
            @foreach (range(1, 6) as $step)
                <div class="hover:bg-base-200/50 flex items-start gap-4 p-4 transition-colors">
                    <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border font-mono text-xs font-bold shadow-2xs">
                        {{ $step }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="text-base-content text-sm font-bold">{{ __('setup.guide.step'.$step.'_title') }}</h4>
                        <p class="text-base-content/60 mt-0.5 text-xs leading-relaxed">
                            {{ __('setup.guide.step'.$step.'_desc') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-warning/20 bg-warning/5 flex items-start gap-3.5 rounded-2xl border p-4 text-xs">
            <div class="border-warning/25 bg-warning/10 text-warning mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-2xs">
                <x-ts-icon name="light-bulb" class="size-4" />
            </div>
            <div class="flex-1">
                <h4 class="text-base-content/90 text-xs font-bold">{{ __('setup.guide.tip_title') }}</h4>
                <p class="text-base-content/60 mt-0.5 leading-relaxed">{{ __('setup.guide.tip_desc') }}</p>
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
    class="bg-primary text-primary-content ring-primary/20 fixed right-6 bottom-6 z-40 flex size-12 cursor-pointer items-center justify-center rounded-full shadow-lg ring-4 transition-all duration-200 hover:scale-105 hover:shadow-xl hover:brightness-105 active:scale-95"
    wire:key="guide-button"
    aria-label="{{ __('setup.guide.title') }}"
>
    <x-ts-icon name="question-mark-circle" class="size-6" />
</button>
