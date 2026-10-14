<div class="flex flex-1 flex-col">
    {{-- Hero Section --}}
    <section class="from-primary/10 via-base-100 to-primary/5 border-base-content/10 relative overflow-hidden border-b bg-gradient-to-br">
        {{-- Ambient decorative background glow --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="bg-primary/10 absolute -top-32 -right-32 size-[32rem] animate-pulse rounded-full blur-3xl"></div>
            <div class="bg-primary/5 absolute -bottom-32 -left-32 size-[32rem] animate-pulse rounded-full blur-3xl"></div>
        </div>

        <div class="relative mx-auto max-w-5xl px-4 py-12 text-center sm:px-6 sm:py-16 lg:px-8">
            <div class="border-primary/20 bg-primary/10 text-primary ring-primary/10 mx-auto mb-6 flex size-20 items-center justify-center rounded-3xl border shadow-md ring-8 sm:size-24">
                <x-ui::components.logo size="12" />
            </div>

            <div class="mb-4 flex flex-wrap items-center justify-center gap-3">
                <h1 class="from-base-content to-base-content/70 bg-gradient-to-br bg-clip-text text-3xl font-black tracking-tight text-transparent sm:text-4xl lg:text-5xl">
                    {{ $appName }}
                </h1>
                <span class="border-primary/30 bg-primary/15 text-primary inline-flex items-center rounded-full border px-3 py-1 font-mono text-xs font-bold shadow-xs">
                    v{{ $version }}
                </span>
                <span class="border-base-content/15 bg-base-100 text-base-content/70 inline-flex items-center rounded-full border px-3 py-1 font-mono text-xs font-semibold">
                    {{ $license }}
                </span>
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
            {{-- Author Profile & Attribution Spotlight Card --}}
            <div class="card bg-base-100 border-base-content/10 border shadow-sm transition-all duration-300 hover:shadow-md">
                <div class="card-body p-6 sm:p-8 lg:p-10">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start md:gap-8">
                        <div class="border-primary/20 bg-primary/10 text-primary ring-primary/10 mx-auto flex size-20 shrink-0 items-center justify-center rounded-2xl border text-2xl font-black shadow-inner ring-4 sm:size-24 sm:text-3xl md:mx-0">
                            {{ strtoupper(substr($authorName, 0, 1)) }}
                        </div>

                        <div class="flex-1 space-y-4 text-center md:text-left">
                            <div class="space-y-1">
                                <div class="flex flex-wrap items-center justify-center gap-2 md:justify-start">
                                    <h2 class="text-base-content text-xl font-bold sm:text-2xl">{{ $authorName }}</h2>
                                    <span class="border-primary/30 bg-primary/10 text-primary rounded-md border px-2.5 py-0.5 text-xs font-semibold">
                                        {{ __('about.author.role') }}
                                    </span>
                                </div>
                                <p class="text-base-content/60 text-xs sm:text-sm">
                                    {{ __('about.author.subheading') }}
                                </p>
                            </div>

                            <p class="text-base-content/80 text-sm leading-relaxed">
                                {{ __('about.author.bio', ['app' => $appName]) }}
                            </p>

                            <div class="flex flex-wrap items-center justify-center gap-3 pt-1 md:justify-start">
                                @if ($authorGithub)
                                    <a
                                        href="{{ $authorGithub }}"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="btn btn-sm btn-outline border-base-content/20 hover:border-primary hover:bg-primary/10 hover:text-primary gap-2"
                                    >
                                        <x-ts-icon name="arrow-top-right-on-square" class="size-4" />
                                        <span>{{ str_replace('https://github.com/', '@', $authorGithub) }}</span>
                                    </a>
                                @endif

                                @if ($authorEmail)
                                    <a
                                        href="mailto:{{ $authorEmail }}"
                                        class="btn btn-sm btn-ghost text-base-content/70 hover:text-primary gap-2"
                                    >
                                        <x-ts-icon name="envelope" class="size-4" />
                                        <span>{{ $authorEmail }}</span>
                                    </a>
                                @endif
                            </div>

                            <div class="border-primary/20 bg-primary/5 rounded-xl border p-3.5 text-left">
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
                </div>
            </div>

            {{-- Philosophy & Principles Section --}}
            <div class="space-y-6">
                <div class="text-center md:text-left">
                    <h2 class="text-base-content text-xl font-bold sm:text-2xl">
                        {{ __('about.philosophy.heading') }}
                    </h2>
                    <p class="text-base-content/60 text-xs sm:text-sm">
                        {{ __('about.philosophy.subheading', ['app' => $appName]) }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="card bg-base-100 border-base-content/10 hover:border-primary/30 border p-6 shadow-xs transition-all">
                        <div class="bg-primary/10 text-primary mb-3 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="academic-cap" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.education_first.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.education_first.desc') }}
                        </p>
                    </div>

                    <div class="card bg-base-100 border-base-content/10 hover:border-primary/30 border p-6 shadow-xs transition-all">
                        <div class="bg-primary/10 text-primary mb-3 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="wrench-screwdriver" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.great_tools.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.great_tools.desc') }}
                        </p>
                    </div>

                    <div class="card bg-base-100 border-base-content/10 hover:border-primary/30 border p-6 shadow-xs transition-all">
                        <div class="bg-primary/10 text-primary mb-3 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="cube" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.simplicity.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.simplicity.desc') }}
                        </p>
                    </div>

                    <div class="card bg-base-100 border-base-content/10 hover:border-primary/30 border p-6 shadow-xs transition-all">
                        <div class="bg-primary/10 text-primary mb-3 flex size-10 items-center justify-center rounded-xl">
                            <x-ts-icon name="shield-check" class="size-5" />
                        </div>
                        <h3 class="text-base-content mb-1.5 text-sm font-bold sm:text-base">
                            {{ __('about.philosophy.data_sovereignty.title') }}
                        </h3>
                        <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                            {{ __('about.philosophy.data_sovereignty.desc') }}
                        </p>
                    </div>

                    <div class="card bg-base-100 border-base-content/10 hover:border-primary/30 border p-6 shadow-xs transition-all md:col-span-2">
                        <div class="bg-primary/10 text-primary mb-3 flex size-10 items-center justify-center rounded-xl">
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
            </div>

            {{-- Technology Architecture Section --}}
            <div class="space-y-6">
                <div class="text-center md:text-left">
                    <h2 class="text-base-content text-xl font-bold sm:text-2xl">
                        {{ __('about.tech_stack.heading') }}
                    </h2>
                    <p class="text-base-content/60 text-xs sm:text-sm">{{ __('about.tech_stack.subheading') }}</p>
                </div>

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div class="card bg-base-100 border-base-content/10 border p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.php') }}</span>
                        </div>
                    </div>
                    <div class="card bg-base-100 border-base-content/10 border p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.laravel') }}</span>
                        </div>
                    </div>
                    <div class="card bg-base-100 border-base-content/10 border p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.livewire') }}</span>
                        </div>
                    </div>
                    <div class="card bg-base-100 border-base-content/10 border p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.tallstackui') }}</span>
                        </div>
                    </div>
                    <div class="card bg-base-100 border-base-content/10 border p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.tailwind') }}</span>
                        </div>
                    </div>
                    <div class="card bg-base-100 border-base-content/10 border p-4 shadow-xs">
                        <div class="flex items-center gap-3">
                            <x-ts-icon name="check-circle" class="text-primary size-5 shrink-0" />
                            <span class="text-base-content/85 text-xs font-semibold sm:text-sm">{{ __('about.tech_stack.pest') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Diagnostics & Open Source Details (2 Columns) --}}
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                {{-- System Diagnostics Card --}}
                <div class="card bg-base-100 border-base-content/10 border shadow-sm">
                    <div
                        class="card-body p-6"
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
                        <div class="mb-2 flex items-center gap-3">
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
                                <span class="badge badge-neutral badge-sm font-mono font-semibold">v{{ $version }}</span>
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

                        <div class="border-base-content/10 mt-3 border-t pt-4">
                            <button
                                type="button"
                                @click="copy()"
                                class="btn btn-outline btn-sm border-base-content/20 hover:border-primary hover:bg-primary/10 hover:text-primary w-full gap-2 text-xs font-semibold"
                            >
                                <x-ts-icon name="clipboard-document" class="size-4" x-show="! copied" />
                                <x-ts-icon name="check" class="text-success size-4" x-show="copied" x-cloak />
                                <span x-text="copied ? '{{ __('about.diagnostics.copied_toast') }}' : '{{ __('about.diagnostics.copy_button') }}'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Open Source & License Card --}}
                <div class="card bg-base-100 border-base-content/10 border shadow-sm">
                    <div class="card-body flex flex-col justify-between p-6">
                        <div class="space-y-3">
                            <div class="flex items-center gap-3">
                                <div class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                                    <x-ts-icon name="code-bracket" class="size-4" />
                                </div>
                                <h3 class="text-base-content text-base font-bold">{{ __('about.license.heading') }}</h3>
                            </div>

                            <p class="text-base-content/70 text-xs leading-relaxed sm:text-sm">
                                {{ __('about.license.text', ['app' => $appName]) }}
                            </p>
                        </div>

                        <div class="border-base-content/10 mt-6 border-t pt-4">
                            <a
                                href="{{ $gitUrl }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="btn btn-primary btn-sm w-full gap-2 text-xs font-semibold shadow-xs"
                            >
                                <span>{{ __('about.license.view_repo') }}</span>
                                <x-ts-icon name="arrow-top-right-on-square" class="size-4" />
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
