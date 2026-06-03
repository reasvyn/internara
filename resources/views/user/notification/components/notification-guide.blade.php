<div x-data="{ showGuide: false }">
    <button
        type="button"
        x-on:click="showGuide = true"
        class="fixed bottom-6 right-6 z-50 flex items-center justify-center size-12 rounded-full shadow-xl bg-primary text-primary-content hover:bg-primary-focus transition-all duration-200 hover:scale-110 active:scale-95"
        aria-label="{{ __('notifications.guide.title') }}"
    >
        <x-mary-icon name="o-question-mark-circle" class="size-6" />
    </button>

    <template x-teleport="body">
        <div x-show="showGuide" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center">
            <div x-on:click="showGuide = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg bg-base-100 rounded-2xl shadow-2xl border border-base-content/10 max-h-[85vh] overflow-y-auto">
                <div class="sticky top-0 bg-base-100 border-b border-base-content/10 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                    <h3 class="text-lg font-bold">{{ __('notifications.guide.title') }}</h3>
                    <button type="button" x-on:click="showGuide = false" class="btn btn-ghost btn-sm btn-square">
                        <x-mary-icon name="o-x-mark" class="size-5" />
                    </button>
                </div>
                <div class="p-6 space-y-5">
                    <p class="text-sm text-base-content/60">{{ __('notifications.guide.intro') }}</p>

                    <div class="flex gap-4">
                        <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary shrink-0 mt-0.5">
                            <x-mary-icon name="o-envelope-open" class="size-4" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm">{{ __('notifications.guide.read_title') }}</h4>
                            <p class="text-xs text-base-content/60 mt-1 leading-relaxed">{{ __('notifications.guide.read_desc') }}</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary shrink-0 mt-0.5">
                            <x-mary-icon name="o-check-badge" class="size-4" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm">{{ __('notifications.guide.mark_title') }}</h4>
                            <p class="text-xs text-base-content/60 mt-1 leading-relaxed">{{ __('notifications.guide.mark_desc') }}</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary shrink-0 mt-0.5">
                            <x-mary-icon name="o-square-2-stack" class="size-4" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm">{{ __('notifications.guide.batch_title') }}</h4>
                            <p class="text-xs text-base-content/60 mt-1 leading-relaxed">{{ __('notifications.guide.batch_desc') }}</p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary shrink-0 mt-0.5">
                            <x-mary-icon name="o-bell" class="size-4" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-sm">{{ __('notifications.guide.bell_title') }}</h4>
                            <p class="text-xs text-base-content/60 mt-1 leading-relaxed">{{ __('notifications.guide.bell_desc') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
