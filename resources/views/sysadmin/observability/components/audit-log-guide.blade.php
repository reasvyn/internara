<div x-data="{ showGuide: false }">
    <button
        type="button"
        x-on:click="showGuide = true"
        class="bg-primary text-primary-content hover:bg-primary-focus fixed right-6 bottom-6 z-50 flex size-12 items-center justify-center rounded-full shadow-xl transition-all duration-200 hover:scale-110 active:scale-95"
        aria-label="{{ __('sysadmin.guide.audit_title') }}"
    >
        <x-ts-icon name="question-mark-circle" class="size-6" />
    </button>

    <template x-teleport="body">
        <div
            x-show="showGuide"
            x-cloak
            x-on:keydown.escape.window="showGuide = false"
            role="dialog"
            aria-modal="true"
            aria-labelledby="guide-title"
            class="fixed inset-0 z-[60] flex items-center justify-center"
        >
            <div x-on:click="showGuide = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
            <div class="bg-base-100 border-base-content/10 relative max-h-[85vh] w-full max-w-lg overflow-y-auto rounded-2xl border shadow-2xl">
                <div class="bg-base-100 border-base-content/10 sticky top-0 flex items-center justify-between rounded-t-2xl border-b px-6 py-4">
                    <h3 id="guide-title" class="text-lg font-bold">{{ __('sysadmin.guide.audit_title') }}</h3>
                    <button
                        type="button"
                        x-on:click="showGuide = false"
                        aria-label="{{ __('common.actions.close') }}"
                        class="btn btn-ghost btn-sm btn-square"
                    >
                        <x-ts-icon name="x-mark" class="size-5" />
                    </button>
                </div>
                <div class="space-y-5 p-6">
                    <p class="text-base-content/60 text-sm">{{ __('sysadmin.guide.audit_intro') }}</p>

                    <div class="flex gap-4">
                        <div class="bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full">
                            <x-ts-icon name="funnel" class="size-4" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold">{{ __('sysadmin.guide.audit_filter_title') }}</h4>
                            <p class="text-base-content/60 mt-1 text-xs leading-relaxed">
                                {{ __('sysadmin.guide.audit_filter_desc') }}
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full">
                            <x-ts-icon name="document-text" class="size-4" />
                        </div>
                        <div>
                            <h4 class="text-sm font-semibold">{{ __('sysadmin.guide.audit_detail_title') }}</h4>
                            <p class="text-base-content/60 mt-1 text-xs leading-relaxed">
                                {{ __('sysadmin.guide.audit_detail_desc') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
