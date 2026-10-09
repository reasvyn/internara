<x-ts-modal wire="showGuide" :title="__('notifications.guide.title')" separator blur size="lg">
    <div class="space-y-4">
        <p class="text-base-content/60 text-sm leading-relaxed">{{ __('notifications.guide.intro') }}</p>

        <div class="divide-base-content/10 border-base-content/10 bg-base-200/30 divide-y overflow-hidden rounded-2xl border">
            <div class="hover:bg-base-200/50 flex items-start gap-4 p-4 transition-colors">
                <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-2xs">
                    <x-ts-icon name="envelope-open" class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-base-content text-sm font-bold">{{ __('notifications.guide.read_title') }}</h4>
                    <p class="text-base-content/60 mt-0.5 text-xs leading-relaxed">
                        {{ __('notifications.guide.read_desc') }}
                    </p>
                </div>
            </div>

            <div class="hover:bg-base-200/50 flex items-start gap-4 p-4 transition-colors">
                <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-2xs">
                    <x-ts-icon name="check-badge" class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-base-content text-sm font-bold">{{ __('notifications.guide.mark_title') }}</h4>
                    <p class="text-base-content/60 mt-0.5 text-xs leading-relaxed">
                        {{ __('notifications.guide.mark_desc') }}
                    </p>
                </div>
            </div>

            <div class="hover:bg-base-200/50 flex items-start gap-4 p-4 transition-colors">
                <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-2xs">
                    <x-ts-icon name="square-2-stack" class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-base-content text-sm font-bold">{{ __('notifications.guide.batch_title') }}</h4>
                    <p class="text-base-content/60 mt-0.5 text-xs leading-relaxed">
                        {{ __('notifications.guide.batch_desc') }}
                    </p>
                </div>
            </div>

            <div class="hover:bg-base-200/50 flex items-start gap-4 p-4 transition-colors">
                <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-2xs">
                    <x-ts-icon name="bell" class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-base-content text-sm font-bold">{{ __('notifications.guide.bell_title') }}</h4>
                    <p class="text-base-content/60 mt-0.5 text-xs leading-relaxed">
                        {{ __('notifications.guide.bell_desc') }}
                    </p>
                </div>
            </div>
        </div>
    </div>

    <x-slot:footer>
        <x-ts-button :text="__('common.actions.close')" wire:click="closeGuide" color="slate" outline sm />
    </x-slot:footer>
</x-ts-modal>
