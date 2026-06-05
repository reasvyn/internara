@props(['title' => null, 'error' => null])

<x-core::layouts.base :$title>
    <div class="min-h-screen flex flex-col bg-base-100">
        <header class="border-b border-base-content/10">
            <div class="max-w-5xl mx-auto px-6 lg:px-12">
                <div class="flex items-center justify-between h-16">
                    <x-core::ui.brand size="sm" :invert="false" />

                    <div class="flex items-center gap-2">
                        <livewire:livewire.theme-switcher class="px-2" />
                        <div class="w-px h-5 bg-base-content/10"></div>
                        <livewire:livewire.lang-switcher class="px-2" />
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 flex items-center justify-center py-12 px-6">
            <div class="w-full max-w-md mx-auto">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center size-14 rounded-full bg-primary/10 text-primary mb-5">
                        <x-mary-icon name="o-key" class="size-7" />
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">{{ __('setup.code_entry.title') }}</h1>
                    <p class="text-sm text-base-content/60 mt-2">
                        {{ __('setup.code_entry.description') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('setup') }}" class="space-y-5">
                    @csrf

                    <div>
                        <x-mary-input
                            label="{{ __('setup.code_entry.code_label') }}"
                            name="setup_token"
                            placeholder="{{ __('setup.code_entry.placeholder') }}"
                            required
                            autofocus
                            autocomplete="off"
                        />
                    </div>

                    <x-mary-button
                        type="submit"
                        label="{{ __('setup.code_entry.submit') }}"
                        icon-right="o-arrow-right"
                        class="btn-primary w-full"
                    />

                    @if ($error)
                        <div class="bg-error/5 border border-error/20 rounded-lg px-4 py-3 text-sm text-error">
                            {{ $error }}
                        </div>
                    @elseif ($errors->any())
                        <div class="bg-error/5 border border-error/20 rounded-lg px-4 py-3 text-sm text-error">
                            {{ $errors->first() }}
                        </div>
                    @endif
                </form>

                <div class="mt-8 text-center">
                    <p class="text-xs text-base-content/40 leading-relaxed">
                        {{ __('setup.code_entry.help') }}
                    </p>
                    <p class="text-xs text-base-content/40 mt-1">
                        {{ __('setup.code_entry.expiry_note') }}
                    </p>
                </div>
            </div>
        </main>

        <footer class="border-t border-base-content/10 py-6 mt-auto">
            <div class="max-w-5xl mx-auto px-6 lg:px-12 text-center">
                <p class="text-xs text-base-content/40">
                    &copy; {{ date('Y') }} {{ brand('author.name') }}. {{ __('All rights reserved.') }}
                </p>
            </div>
        </footer>
    </div>

    <div x-data="{ showGuide: false }">
        <button
            type="button"
            x-on:click="showGuide = true"
            class="fixed bottom-6 right-6 z-50 flex items-center justify-center size-12 rounded-full shadow-xl bg-primary text-primary-content hover:bg-primary-focus transition-all duration-200 hover:scale-110 active:scale-95"
            aria-label="{{ __('setup.guide.title') }}"
        >
            <x-mary-icon name="o-question-mark-circle" class="size-6" />
        </button>

        <template x-teleport="body">
            <div x-show="showGuide" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center">
                <div x-on:click="showGuide = false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
                <div class="relative w-full max-w-lg bg-base-100 rounded-2xl shadow-2xl border border-base-content/10 max-h-[85vh] overflow-y-auto">
                    <div class="sticky top-0 bg-base-100 border-b border-base-content/10 px-6 py-4 flex items-center justify-between rounded-t-2xl">
                        <h3 class="text-lg font-bold">{{ __('setup.guide.title') }}</h3>
                        <button type="button" x-on:click="showGuide = false" class="btn btn-ghost btn-sm btn-square">
                            <x-mary-icon name="o-x-mark" class="size-5" />
                        </button>
                    </div>
                    <div class="p-6 space-y-5">
                        <p class="text-sm text-base-content/60">{{ __('setup.guide.intro') }}</p>

                        @foreach(range(1, 7) as $step)
                            <div class="flex gap-4">
                                <div class="flex items-center justify-center size-8 rounded-full bg-primary/10 text-primary font-bold text-sm shrink-0 mt-0.5">{{ $step }}</div>
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
                </div>
            </div>
        </template>
    </div>
</x-core::layouts.base>
