@props(['title' => null, 'error' => null])

<x-ui::layouts.base :$title>
    <div class="bg-base-200/60 text-base-content selection:bg-primary/20 selection:text-primary relative flex min-h-screen flex-col">
        {{-- Ambient decorative background glow --}}
        <div class="pointer-events-none absolute inset-0 -z-10 overflow-hidden" aria-hidden="true">
            <div class="bg-primary/5 absolute -top-40 left-1/2 size-[700px] -translate-x-1/2 rounded-full blur-3xl"></div>
            <div class="bg-primary/3 absolute top-1/2 -right-40 size-[500px] rounded-full blur-3xl"></div>
        </div>

        <header class="border-base-content/10 bg-base-100/80 sticky top-0 z-30 border-b backdrop-blur-md transition-all">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-13 items-center justify-between sm:h-14">
                    <x-ui::components.brand size="sm" :invert="false" />

                    <div class="flex items-center gap-2">
                        <div class="border-base-content/10 bg-base-100/90 flex items-center gap-1.5 rounded-md border px-2.5 py-0.5 shadow-xs sm:py-1">
                            <x-setting::locale.theme-switch size="xs" />
                            <div class="bg-base-content/10 h-3.5 w-px sm:h-4"></div>
                            <livewire:setting.locale.lang-switch class="px-1" />
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex flex-1 items-center justify-center px-4 py-6 sm:px-6">
            <div class="mx-auto w-full max-w-md">
                <div class="border-base-content/10 bg-base-100 relative overflow-hidden rounded-lg border p-5 shadow-sm sm:p-7">
                    <div
                        class="from-primary via-primary/80 to-primary/40 absolute inset-x-0 top-0 h-1 bg-gradient-to-r"
                        aria-hidden="true"
                    ></div>

                    <div class="mb-5 text-center sm:mb-6">
                        <div class="border-primary/20 bg-primary/10 text-primary ring-primary/10 mb-3.5 inline-flex size-12 items-center justify-center rounded-md border shadow-xs ring-4 sm:mb-4 sm:size-14">
                            <x-ts-icon name="key" class="size-6 sm:size-7" />
                        </div>
                        <h1 class="text-base-content text-xl font-black tracking-tight sm:text-2xl">
                            {{ __('setup.code_entry.title') }}
                        </h1>
                        <p class="text-base-content/60 mt-1 text-xs leading-relaxed sm:mt-1.5 sm:text-sm">
                            {{ __('setup.code_entry.description') }}
                        </p>
                    </div>

                    <div
                        x-data="{
                            digits: [
                                '{{ strtoupper(substr(old('setup_token', request('setup_token', '')), 0, 1)) }}',
                                '{{ strtoupper(substr(old('setup_token', request('setup_token', '')), 1, 1)) }}',
                                '{{ strtoupper(substr(old('setup_token', request('setup_token', '')), 2, 1)) }}',
                                '{{ strtoupper(substr(old('setup_token', request('setup_token', '')), 3, 1)) }}',
                                '{{ strtoupper(substr(old('setup_token', request('setup_token', '')), 4, 1)) }}',
                                '{{ strtoupper(substr(old('setup_token', request('setup_token', '')), 5, 1)) }}',
                            ],
                            get fullToken() {
                                return this.digits.join('');
                            },
                            handleInput(index, event) {
                                const val = event.target.value.replace(/[^a-zA-Z0-9]/g, '').toUpperCase();
                                this.digits[index] = val ? val.slice(-1) : '';
                                if (this.digits[index] && index < 5) {
                                    this.$refs['digit' + (index + 1)].focus();
                                    this.$refs['digit' + (index + 1)].select();
                                }
                            },
                            handleKeydown(index, event) {
                                const k = event.key.toLowerCase();
                                if (k === 'backspace' && ! this.digits[index] && index > 0) {
                                    this.$refs['digit' + (index - 1)].focus();
                                } else if (k === 'arrowleft' && index > 0) {
                                    this.$refs['digit' + (index - 1)].focus();
                                } else if (k === 'arrowright' && index < 5) {
                                    this.$refs['digit' + (index + 1)].focus();
                                }
                            },
                            handlePaste(event) {
                                event.preventDefault();
                                const text = (event.clipboardData || window.clipboardData).getData('text') || '';
                                const cleaned = text.replace(/[^a-zA-Z0-9]/g, '').toUpperCase().slice(0, 6);
                                if (! cleaned) return;
                                for (let i = 0; i < 6; i++) {
                                    this.digits[i] = cleaned[i] || '';
                                }
                                const targetIdx = cleaned.length < 6 ? cleaned.length : 5;
                                this.$nextTick(() => {
                                    this.$refs['digit' + targetIdx]?.focus();
                                    this.$refs['digit' + targetIdx]?.select();
                                });
                            }
                        }"
                    >
                        <form method="POST" action="{{ route('setup') }}" class="space-y-4 sm:space-y-5">
                            @csrf
                            <input type="hidden" name="setup_token" :value="fullToken" />

                            <div class="space-y-2.5">
                                <label class="text-base-content/80 block text-center text-xs font-semibold tracking-wider uppercase">
                                    {{ __('setup.code_entry.code_label') }} <span class="text-error">*</span>
                                </label>

                                <div
                                    class="flex items-center justify-center gap-1.5 sm:gap-2.5"
                                    @paste="handlePaste($event)"
                                >
                                    @for ($i = 0; $i < 6; $i++)
                                        <input
                                            type="text"
                                            maxlength="1"
                                            inputmode="text"
                                            autocomplete="off"
                                            autocapitalize="characters"
                                            spellcheck="false"
                                            x-ref="digit{{ $i }}"
                                            x-model="digits[{{ $i }}]"
                                            @input="handleInput({{ $i }}, $event)"
                                            @keydown="handleKeydown({{ $i }}, $event)"
                                            @focus="$event.target.select()"
                                            @if ($i === 0) autofocus @endif
                                            aria-label="{{ __('setup.code_entry.code_label') }} {{ $i + 1 }}"
                                            class="bg-base-100 border-base-content/20 text-base-content focus:border-primary focus:ring-primary/20 size-10.5 rounded-md border text-center font-mono text-lg font-black uppercase shadow-xs transition-all focus:ring-4 focus:outline-hidden sm:size-12 sm:text-xl"
                                        />
                                    @endfor
                                </div>
                            </div>

                            <x-ts-button
                                type="submit"
                                :text="__('setup.code_entry.submit')"
                                icon="arrow-right"
                                position="right"
                                class="w-full"
                                color="primary"
                            />

                            @if ($error)
                                <div class="bg-error/5 border-error/20 text-error flex items-center gap-2 rounded-md border p-3 text-xs sm:text-sm">
                                    <x-ts-icon name="x-circle" class="size-4 shrink-0 sm:size-5" />
                                    <span>{{ $error }}</span>
                                </div>
                            @elseif ($errors->any())
                                <div class="bg-error/5 border-error/20 text-error flex items-center gap-2 rounded-md border p-3 text-xs sm:text-sm">
                                    <x-ts-icon name="x-circle" class="size-4 shrink-0 sm:size-5" />
                                    <span>{{ $errors->first() }}</span>
                                </div>
                            @endif
                        </form>
                    </div>

                    <div class="border-base-content/10 bg-base-200/40 mt-5 rounded-md border p-3 text-center sm:mt-6 sm:p-3.5">
                        <p class="text-base-content/60 text-xs leading-relaxed">{{ __('setup.code_entry.help') }}</p>
                        <p class="text-base-content/40 mt-0.5 text-xs">{{ __('setup.code_entry.expiry_note') }}</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="border-base-content/10 bg-base-100/50 mt-auto border-t py-2.5 backdrop-blur-xs sm:py-3">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-2 text-center sm:flex-row sm:text-left">
                    <p class="text-base-content/50 text-xs">
                        &copy; {{ date('Y') }} {{ brand('author.name') }}. {{ __('All rights reserved.') }}
                    </p>
                    <div class="flex items-center gap-2">
                        <span class="border-base-content/10 bg-base-100 text-base-content/70 inline-flex items-center gap-1.5 rounded-md border px-2.5 py-0.5 font-mono text-[11px] shadow-xs">
                            <span class="bg-success size-1.5 rounded-full"></span>
                            v{{ app_info('version') }}
                        </span>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    <div x-data="{ showGuide: false }">
        <button
            type="button"
            x-on:click="showGuide = true"
            class="bg-primary text-primary-content ring-primary/20 hover:bg-primary/80 fixed right-6 bottom-6 z-40 flex size-12 items-center justify-center rounded-md shadow-lg ring-4 transition-all duration-200 hover:scale-105 hover:shadow-xl active:scale-95"
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
                            class="text-base-content/50 hover:text-base-content hover:bg-base-200 flex size-8 items-center justify-center rounded-lg transition-colors"
                        >
                            <x-ts-icon name="x-mark" class="size-5" />
                        </button>
                    </div>
                    <div class="space-y-5 p-6">
                        <p class="text-base-content/60 text-sm">{{ __('setup.guide.intro') }}</p>

                        @foreach (range(1, 7) as $step)
                            <div class="flex gap-4">
                                <div class="border-primary/20 bg-primary/10 text-primary mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border text-xs font-bold shadow-2xs">
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
                            <div class="border-warning/20 bg-warning/10 text-warning mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-xl border shadow-2xs">
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
