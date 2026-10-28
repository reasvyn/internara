<div class="flex flex-1 flex-col">
    {{-- Hero Section --}}
    <section class="from-primary/10 via-base-100 to-primary/5 border-base-content/10 relative overflow-hidden border-b bg-gradient-to-br">
        {{-- Ambient decorative background glow --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="bg-primary/10 absolute -top-32 -right-32 size-[32rem] animate-pulse rounded-full blur-3xl"></div>
            <div class="bg-primary/5 absolute -bottom-32 -left-32 size-[32rem] animate-pulse rounded-full blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-5xl px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8">
            <div class="border-primary/20 bg-primary/10 text-primary ring-primary/10 mx-auto mb-6 flex size-20 items-center justify-center rounded-2xl border ring-4 sm:size-24">
                <x-ui::components.logo size="12" />
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-center gap-3">
                <h1 class="from-base-content to-base-content/70 bg-gradient-to-br bg-clip-text text-3xl font-black tracking-tight text-transparent sm:text-4xl lg:text-5xl">
                    {{ $appName }}
                </h1>
                <x-ts-badge :text="'v'.$version" color="primary" light round sm class="font-mono font-bold" />
                <x-ts-badge :text="$license" color="neutral" outline round sm class="font-mono font-semibold" />
            </div>

            <p class="text-primary mx-auto mb-4 max-w-2xl text-base font-semibold sm:text-lg">{{ $tagline }}</p>

            <p class="text-base-content/70 mx-auto max-w-3xl text-sm leading-relaxed sm:text-base">
                {{ __('about.overview.description', ['app' => $appName]) }}
            </p>
        </div>
    </section>

    {{-- Main Content Section --}}
    <section class="bg-base-200/40 flex-1 py-10 sm:py-14 lg:py-16">
        <div class="mx-auto max-w-5xl space-y-12 px-4 sm:px-6 lg:px-8">
            {{-- PKL Educational Context Card --}}
            <article class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-2xl border p-6 transition-all duration-300 sm:p-8 lg:p-10">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:gap-6">
                    <div class="border-primary/20 bg-primary/10 text-primary ring-primary/10 mx-auto flex size-16 shrink-0 items-center justify-center rounded-2xl border ring-4 sm:size-20 md:mx-0">
                        <x-ts-icon name="academic-cap" class="size-8 sm:size-10" />
                    </div>

                    <div class="flex-1 space-y-2 text-center sm:text-left">
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <h2 class="text-base-content text-xl font-bold sm:text-2xl">{{ __('about.pkl.title') }}</h2>
                            <x-ts-badge :text="__('about.pkl.badge')" color="primary" light sm />
                        </div>

                        <p class="text-base-content/75 text-sm leading-relaxed sm:text-base">
                            {{ __('about.pkl.description') }}
                        </p>
                    </div>
                </div>
            </article>

            {{-- Author Profile & Attribution Spotlight Card --}}
            <article class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-2xl border p-6 transition-all duration-300 sm:p-8 lg:p-10">
                <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                    <div class="border-primary/20 bg-primary/10 text-primary ring-primary/10 mx-auto flex size-20 shrink-0 items-center justify-center rounded-2xl border text-2xl font-black ring-4 sm:size-24 sm:text-3xl md:mx-0">
                        {{ strtoupper(substr($authorName, 0, 1)) }}
                    </div>

                    <div class="flex-1 space-y-4 text-center md:text-left">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center justify-center gap-2 md:justify-start">
                                <h2 class="text-base-content text-xl font-bold sm:text-2xl">{{ $authorName }}</h2>
                                <x-ts-badge :text="__('about.author.role')" color="primary" light sm />
                            </div>
                            <p class="text-base-content/60 text-xs sm:text-sm">{{ __('about.author.subheading') }}</p>
                        </div>

                        <p class="text-base-content/80 text-sm leading-relaxed">
                            {{ __('about.author.bio', ['app' => $appName]) }}
                        </p>

                        <div class="flex flex-wrap items-center justify-center gap-3 pt-1 md:justify-start">
                            @if ($authorGithub)
                                <x-ts-button
                                    href="{{ $authorGithub }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    outline
                                    sm
                                    icon="arrow-top-right-on-square"
                                    position="right"
                                    :text="str_replace('https://github.com/', '@', $authorGithub)"
                                />
                            @endif

                            @if ($authorEmail)
                                <x-ts-button
                                    href="mailto:{{ $authorEmail }}"
                                    flat
                                    sm
                                    icon="envelope"
                                    :text="$authorEmail"
                                />
                            @endif
                        </div>

                        <div class="border-primary/20 bg-primary/5 rounded-xl border p-4 text-left">
                            <p class="text-base-content/70 text-xs leading-relaxed">
                                <x-ts-icon
                                    name="shield-check"
                                    class="text-primary mr-1.5 inline size-4 align-text-bottom"
                                />
                                {{ __('about.author.attribution_notice') }}
                            </p>
                        </div>
                    </div>
                </div>
            </article>

            {{-- Philosophy & Principles Section --}}
            <section class="space-y-6">
                <div class="text-center md:text-left">
                    <h2 class="text-base-content text-xl font-bold sm:text-2xl">
                        {{ __('about.philosophy.heading') }}
                    </h2>
                    <p class="text-base-content/60 text-xs sm:text-sm">
                        {{ __('about.philosophy.subheading', ['app' => $appName]) }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-6 transition-all duration-200">
                        <div class="bg-primary/10 text-primary mb-3.5 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="academic-cap" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.education_first.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.education_first.desc') }}
                        </p>
                    </div>

                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-6 transition-all duration-200">
                        <div class="bg-primary/10 text-primary mb-3.5 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="wrench-screwdriver" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.great_tools.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.great_tools.desc') }}
                        </p>
                    </div>

                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-6 transition-all duration-200">
                        <div class="bg-primary/10 text-primary mb-3.5 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="cube" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.simplicity.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.simplicity.desc') }}
                        </p>
                    </div>

                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-6 transition-all duration-200">
                        <div class="bg-primary/10 text-primary mb-3.5 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="shield-check" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.data_sovereignty.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.data_sovereignty.desc') }}
                        </p>
                    </div>

                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-6 transition-all duration-200 md:col-span-2">
                        <div class="bg-primary/10 text-primary mb-3.5 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="arrow-path" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.maintainability.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.maintainability.desc') }}
                        </p>
                    </div>
                </div>
            </section>

            {{-- Technology Architecture Section --}}
            <section class="space-y-6">
                <div class="text-center md:text-left">
                    <h2 class="text-base-content text-xl font-bold sm:text-2xl">
                        {{ __('about.tech_stack.heading') }}
                    </h2>
                    <p class="text-base-content/60 text-xs sm:text-sm">{{ __('about.tech_stack.subheading') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-4 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.php') }}</span>
                        </div>
                    </div>
                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-4 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.laravel') }}</span>
                        </div>
                    </div>
                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-4 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.livewire') }}</span>
                        </div>
                    </div>
                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-4 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.tallstackui') }}</span>
                        </div>
                    </div>
                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-4 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.tailwind') }}</span>
                        </div>
                    </div>
                    <div class="bg-base-100 border-base-content/10 hover:border-primary/30 rounded-xl border p-4 transition-colors">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.pest') }}</span>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Diagnostics & Open Source Details (2 Columns) --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- System Diagnostics Card --}}
                <div
                    class="bg-base-100 border-base-content/10 flex flex-col justify-between rounded-xl border p-6"
                    x-data="{
                        copied: false,
                        snippet: @js($diagnosticSnippet),
                        copy() {
                            if (navigator.clipboard) {
                                navigator.clipboard.writeText(this.snippet);
                                this.copied = true;
                                setTimeout(() => this.copied = false, 2500);
                            }
                        }
                    }"
                >
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                                <x-ts-icon name="information-circle" class="size-4" />
                            </div>
                            <div>
                                <h3 class="text-base-content text-base font-bold">
                                    {{ __('about.diagnostics.heading') }}
                                </h3>
                                <p class="text-base-content/60 text-xs">{{ __('about.diagnostics.subheading') }}</p>
                            </div>
                        </div>

                        <div class="space-y-2.5 pt-2 text-xs">
                            <div class="border-base-content/5 flex items-center justify-between border-b pb-2">
                                <span class="text-base-content/60">{{ __('about.diagnostics.app_version') }}</span>
                                <x-ts-badge
                                    :text="'v'.$version"
                                    color="neutral"
                                    light
                                    sm
                                    class="font-mono font-semibold"
                                />
                            </div>
                            <div class="border-base-content/5 flex items-center justify-between border-b pb-2">
                                <span class="text-base-content/60">{{ __('about.diagnostics.environment') }}</span>
                                <span class="text-base-content font-semibold uppercase">{{ $environment }}</span>
                            </div>
                            <div class="border-base-content/5 flex items-center justify-between border-b pb-2">
                                <span class="text-base-content/60">{{ __('about.diagnostics.php_version') }}</span>
                                <span class="text-base-content font-mono">{{ $phpVersion }}</span>
                            </div>
                            <div class="border-base-content/5 flex items-center justify-between border-b pb-2">
                                <span class="text-base-content/60">{{ __('about.diagnostics.laravel_version') }}</span>
                                <span class="text-base-content font-mono">{{ $laravelVersion }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/60">{{ __('about.diagnostics.license') }}</span>
                                <span class="text-base-content font-medium">{{ $license }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="border-base-content/10 mt-6 border-t pt-4">
                        <x-ts-button
                            type="button"
                            x-on:click="copy()"
                            outline
                            sm
                            block
                            class="w-full text-xs font-semibold"
                        >
                            <x-ts-icon name="clipboard-document" class="mr-1.5 size-4" x-show="! copied" />
                            <x-ts-icon name="check" class="text-success mr-1.5 size-4" x-show="copied" x-cloak />
                            <span x-text="copied ? '{{ __('about.diagnostics.copied_toast') }}' : '{{ __('about.diagnostics.copy_button') }}'"></span>
                        </x-ts-button>
                    </div>
                </div>

                {{-- Open Source & License Card --}}
                <div class="bg-base-100 border-base-content/10 flex flex-col justify-between rounded-xl border p-6">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                                <x-ts-icon name="code-bracket" class="size-4" />
                            </div>
                            <div>
                                <h3 class="text-base-content text-base font-bold">{{ __('about.license.heading') }}</h3>
                                <p class="text-base-content/60 text-xs">
                                    {{ $license }} {{ __('about.license.view_repo') }}
                                </p>
                            </div>
                        </div>

                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.license.text', ['app' => $appName]) }}
                        </p>
                    </div>

                    <div class="border-base-content/10 mt-6 border-t pt-4">
                        <x-ts-button
                            href="{{ $gitUrl }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            color="primary"
                            sm
                            block
                            icon="arrow-top-right-on-square"
                            position="right"
                            :text="__('about.license.view_repo')"
                        />
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
