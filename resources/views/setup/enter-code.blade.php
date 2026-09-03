@props(['title' => null, 'error' => null])

<x-ui::layouts.base :$title>
    <div class="bg-base-100 flex min-h-screen flex-col">
        <header class="border-base-content/10 border-b">
            <div class="mx-auto max-w-5xl px-6 lg:px-12">
                <div class="flex h-16 items-center justify-between">
                    <x-ui::components.brand size="sm" :invert="false" />

                    <div class="flex items-center gap-2">
                        <div class="inline-flex">
                            <x-ui::components.theme-switch />
                        </div>
                        <div class="bg-base-content/10 h-5 w-px"></div>
                        <livewire:settings.lang-switcher class="px-2" />
                    </div>
                </div>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-6 py-12">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-8 text-center">
                    <div class="bg-primary/10 text-primary mb-5 inline-flex size-14 items-center justify-center rounded-full">
                        <x-ts-icon name="key" class="size-7" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ __('setup.code_entry.title') }}</h1>
                    <p class="text-base-content/60 mt-2 text-sm">{{ __('setup.code_entry.description') }}</p>
                </div>

                <form method="POST" action="{{ route('setup') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-ts-input
                            label="{{ __('setup.code_entry.code_label') }}"
                            name="setup_token"
                            placeholder="{{ __('setup.code_entry.placeholder') }}"
                            required
                            autofocus
                            autocomplete="off"
                        />
                    </div>

                    <x-ts-button
                        type="submit"
                        text="{{ __('setup.code_entry.submit') }}"
                        icon-right="o-arrow-right"
                        class="w-full"
                        color="primary"
                    />

                    @if ($error)
                        <div class="bg-error/5 border-error/20 text-error rounded-lg border px-4 py-3 text-sm">
                            {{ $error }}
                        </div>
                    @elseif ($errors->any())
                        <div class="bg-error/5 border-error/20 text-error rounded-lg border px-4 py-3 text-sm">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </form>

                <div class="mt-8 text-center">
                    <p class="text-base-content/40 text-xs leading-relaxed">{{ __('setup.code_entry.help') }}</p>
                    <p class="text-base-content/40 mt-1 text-xs">{{ __('setup.code_entry.expiry_note') }}</p>
                </div>
            </div>
        </main>

        <footer class="border-base-content/10 mt-auto border-t py-6">
            <div class="mx-auto max-w-5xl px-6 text-center lg:px-12">
                <p class="text-base-content/40 text-xs">
                    &copy; {{ date('Y') }} {{ brand('author.name') }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </footer>
    </div>

    <div x-data="{ showGuide: false }">
        <button
            type="button"
            x-on:click="showGuide = true"
            class="bg-primary text-primary-content hover:bg-primary-focus fixed right-6 bottom-6 z-50 flex size-12 items-center justify-center rounded-full shadow-xl transition-all duration-200 hover:scale-110 active:scale-95"
            aria-label="{{ __('setup.guide.title') }}"
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
                        <h3 id="guide-title" class="text-lg font-bold">{{ __('setup.guide.title') }}</h3>
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
                        <p class="text-base-content/60 text-sm">{{ __('setup.guide.intro') }}</p>

                        @foreach (range(1, 7) as $step)
                            <div class="flex gap-4">
                                <div class="bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-bold">
                                    {{ $step }}
                                </div>
                                <div>
                                    <h4 class="text-sm font-semibold">
                                        {{
                                            __(
                                                'setup.guide.step'.$step.'_title',
                                            )
                                        }}
                                    </h4>
                                    <p class="text-base-content/60 mt-1 text-xs leading-relaxed">
                                        {{
                                            __(
                                                'setup.guide.step'.$step.'_desc',
                                            )
                                        }}
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
                                <p class="text-base-content/60 mt-1 text-xs leading-relaxed">
                                    {{ __('setup.guide.tip_desc') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-ui::layouts.base>
