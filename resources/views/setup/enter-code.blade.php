@props(['title' => null])

<x-layouts::base :$title>
    <div class="min-h-screen flex flex-col bg-base-100">
        <header class="border-b border-base-content/10">
            <div class="max-w-5xl mx-auto px-6 lg:px-12">
                <div class="flex items-center justify-between h-16">
                    <x-shared::ui.brand size="sm" :invert="false" />

                    <div class="flex items-center gap-2">
                        <x-shared::ui.theme-switcher class="px-2" />
                        <div class="w-px h-5 bg-base-content/10"></div>
                        <x-shared::ui.lang-switcher class="px-2" />
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
                            placeholder="e.g., a1b2c3d4e5f6g7h8i9j0..."
                            required
                            autofocus
                            autocomplete="off"
                        />
                    </div>

                    <x-mary-button
                        type="submit"
                        label="{{ __('setup.code_entry.submit') }}"
                        icon="o-arrow-right"
                        class="btn-primary w-full"
                    />

                    @if ($errors->any())
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
</x-layouts::base>
