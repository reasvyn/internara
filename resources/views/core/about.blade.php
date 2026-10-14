<div class="space-y-8 py-4 sm:py-6 lg:py-8">
    {{-- Hero Section --}}
    <x-ts-card shadowless class="border-base-content/10 bg-base-100 overflow-hidden border">
        <div class="flex flex-col items-center gap-6 p-4 text-center sm:p-8 sm:text-left md:flex-row md:items-start md:gap-8">
            <div class="border-primary/20 bg-primary/10 text-primary ring-primary/10 flex size-20 shrink-0 items-center justify-center rounded-2xl border shadow-sm ring-4 sm:size-24">
                <x-ui::components.logo size="12" />
            </div>

            <div class="min-w-0 flex-1 space-y-3">
                <div class="flex flex-wrap items-center justify-center gap-3 sm:justify-start">
                    <h1 class="text-base-content text-2xl font-black tracking-tight sm:text-3xl lg:text-4xl">
                        {{ $appName }}
                    </h1>
                    <x-ts-badge :text="'v'.$version" color="primary" class="font-mono text-xs font-semibold" />
                    <x-ts-badge :text="$license" color="gray" class="font-mono text-xs" />
                </div>

                <p class="text-primary text-sm font-semibold sm:text-base">{{ $tagline }}</p>

                <p class="text-base-content/70 max-w-3xl text-xs leading-relaxed sm:text-sm">
                    {{ __('about.overview.description', ['app' => $appName]) }}
                </p>
            </div>
        </div>
    </x-ts-card>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left 2 cols: Philosophy & Tech Stack --}}
        <div class="space-y-6 lg:col-span-2">
            {{-- Philosophy Cards --}}
            <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                            <x-ts-icon name="sparkles" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-base-content text-base font-bold sm:text-lg">
                                {{ __('about.philosophy.heading') }}
                            </h2>
                            <p class="text-base-content/60 text-xs">
                                {{ __('about.philosophy.subheading', ['app' => $appName]) }}
                            </p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="grid grid-cols-1 gap-4 pt-2 sm:grid-cols-2">
                    <div class="border-base-content/10 bg-base-200/40 space-y-1.5 rounded-xl border p-4">
                        <div class="text-primary flex items-center gap-2 text-xs font-bold sm:text-sm">
                            <x-ts-icon name="academic-cap" class="size-4 shrink-0" />
                            <span>{{ __('about.philosophy.education_first.title') }}</span>
                        </div>
                        <p class="text-base-content/70 text-xs leading-relaxed">
                            {{ __('about.philosophy.education_first.desc') }}
                        </p>
                    </div>

                    <div class="border-base-content/10 bg-base-200/40 space-y-1.5 rounded-xl border p-4">
                        <div class="text-primary flex items-center gap-2 text-xs font-bold sm:text-sm">
                            <x-ts-icon name="wrench-screwdriver" class="size-4 shrink-0" />
                            <span>{{ __('about.philosophy.great_tools.title') }}</span>
                        </div>
                        <p class="text-base-content/70 text-xs leading-relaxed">
                            {{ __('about.philosophy.great_tools.desc') }}
                        </p>
                    </div>

                    <div class="border-base-content/10 bg-base-200/40 space-y-1.5 rounded-xl border p-4">
                        <div class="text-primary flex items-center gap-2 text-xs font-bold sm:text-sm">
                            <x-ts-icon name="cube" class="size-4 shrink-0" />
                            <span>{{ __('about.philosophy.simplicity.title') }}</span>
                        </div>
                        <p class="text-base-content/70 text-xs leading-relaxed">
                            {{ __('about.philosophy.simplicity.desc') }}
                        </p>
                    </div>

                    <div class="border-base-content/10 bg-base-200/40 space-y-1.5 rounded-xl border p-4">
                        <div class="text-primary flex items-center gap-2 text-xs font-bold sm:text-sm">
                            <x-ts-icon name="shield-check" class="size-4 shrink-0" />
                            <span>{{ __('about.philosophy.data_sovereignty.title') }}</span>
                        </div>
                        <p class="text-base-content/70 text-xs leading-relaxed">
                            {{ __('about.philosophy.data_sovereignty.desc') }}
                        </p>
                    </div>

                    <div class="border-base-content/10 bg-base-200/40 col-span-1 space-y-1.5 rounded-xl border p-4 sm:col-span-2">
                        <div class="text-primary flex items-center gap-2 text-xs font-bold sm:text-sm">
                            <x-ts-icon name="arrow-path" class="size-4 shrink-0" />
                            <span>{{ __('about.philosophy.maintainability.title') }}</span>
                        </div>
                        <p class="text-base-content/70 text-xs leading-relaxed">
                            {{ __('about.philosophy.maintainability.desc') }}
                        </p>
                    </div>
                </div>
            </x-ts-card>

            {{-- Tech Stack Card --}}
            <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                            <x-ts-icon name="cpu-chip" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-base-content text-base font-bold sm:text-lg">
                                {{ __('about.tech_stack.heading') }}
                            </h2>
                            <p class="text-base-content/60 text-xs">{{ __('about.tech_stack.subheading') }}</p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="grid grid-cols-1 gap-3 pt-2 sm:grid-cols-2">
                    <div class="border-base-content/10 bg-base-200/30 flex items-center gap-3 rounded-lg border p-3 text-xs">
                        <x-ts-icon name="check-circle" class="text-primary size-4 shrink-0" />
                        <span class="text-base-content/80 font-medium">{{ __('about.tech_stack.php') }}</span>
                    </div>
                    <div class="border-base-content/10 bg-base-200/30 flex items-center gap-3 rounded-lg border p-3 text-xs">
                        <x-ts-icon name="check-circle" class="text-primary size-4 shrink-0" />
                        <span class="text-base-content/80 font-medium">{{ __('about.tech_stack.laravel') }}</span>
                    </div>
                    <div class="border-base-content/10 bg-base-200/30 flex items-center gap-3 rounded-lg border p-3 text-xs">
                        <x-ts-icon name="check-circle" class="text-primary size-4 shrink-0" />
                        <span class="text-base-content/80 font-medium">{{ __('about.tech_stack.livewire') }}</span>
                    </div>
                    <div class="border-base-content/10 bg-base-200/30 flex items-center gap-3 rounded-lg border p-3 text-xs">
                        <x-ts-icon name="check-circle" class="text-primary size-4 shrink-0" />
                        <span class="text-base-content/80 font-medium">{{ __('about.tech_stack.tallstackui') }}</span>
                    </div>
                    <div class="border-base-content/10 bg-base-200/30 flex items-center gap-3 rounded-lg border p-3 text-xs">
                        <x-ts-icon name="check-circle" class="text-primary size-4 shrink-0" />
                        <span class="text-base-content/80 font-medium">{{ __('about.tech_stack.tailwind') }}</span>
                    </div>
                    <div class="border-base-content/10 bg-base-200/30 flex items-center gap-3 rounded-lg border p-3 text-xs">
                        <x-ts-icon name="check-circle" class="text-primary size-4 shrink-0" />
                        <span class="text-base-content/80 font-medium">{{ __('about.tech_stack.pest') }}</span>
                    </div>
                </div>
            </x-ts-card>
        </div>

        {{-- Right col: Author Profile, License & Diagnostics --}}
        <div class="space-y-6">
            {{-- Author Card --}}
            <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                            <x-ts-icon name="user" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-base-content text-base font-bold">{{ __('about.author.heading') }}</h2>
                            <p class="text-base-content/60 text-xs">{{ __('about.author.subheading') }}</p>
                        </div>
                    </div>
                </x-slot:header>

                <div class="space-y-4 pt-2">
                    <div class="flex items-center gap-4">
                        <div class="border-primary/20 bg-primary/10 text-primary flex size-12 items-center justify-center rounded-xl border text-lg font-black">
                            {{ strtoupper(substr($authorName, 0, 1)) }}
                        </div>
                        <div>
                            <h3 class="text-base-content text-sm font-bold sm:text-base">{{ $authorName }}</h3>
                            <p class="text-primary text-xs font-semibold">{{ __('about.author.role') }}</p>
                        </div>
                    </div>

                    <p class="text-base-content/70 text-xs leading-relaxed">
                        {{ __('about.author.bio', ['app' => $appName]) }}
                    </p>

                    <div class="border-base-content/10 space-y-2 border-t pt-3 text-xs">
                        @if ($authorGithub)
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/60">{{ __('about.author.github_label') }}</span>
                                <a
                                    href="{{ $authorGithub }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="text-primary font-semibold hover:underline"
                                >
                                    {{ str_replace('https://github.com/', '@', $authorGithub) }}
                                </a>
                            </div>
                        @endif

                        @if ($authorEmail)
                            <div class="flex items-center justify-between">
                                <span class="text-base-content/60">{{ __('about.author.email_label') }}</span>
                                <a href="mailto:{{ $authorEmail }}" class="text-primary font-medium hover:underline">
                                    {{ $authorEmail }}
                                </a>
                            </div>
                        @endif
                    </div>

                    <div class="border-primary/20 bg-primary/5 rounded-xl border p-3">
                        <p class="text-base-content/60 text-[11px] leading-relaxed">
                            <x-ts-icon name="shield-check" class="text-primary mr-1 inline size-3.5" />
                            {{ __('about.author.attribution_notice') }}
                        </p>
                    </div>
                </div>
            </x-ts-card>

            {{-- System Diagnostics Card with One-Click Copy --}}
            <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                <x-slot:header>
                    <div class="flex items-center gap-3">
                        <div class="bg-primary/10 text-primary flex size-8 items-center justify-center rounded-lg">
                            <x-ts-icon name="information-circle" class="size-4" />
                        </div>
                        <div>
                            <h2 class="text-base-content text-base font-bold">{{ __('about.diagnostics.heading') }}</h2>
                            <p class="text-base-content/60 text-xs">{{ __('about.diagnostics.subheading') }}</p>
                        </div>
                    </div>
                </x-slot:header>

                <div
                    class="space-y-3 pt-2 text-xs"
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
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/60">{{ __('about.diagnostics.app_version') }}</span>
                        <x-ts-badge :text="'v'.$version" class="badge-neutral badge-sm font-mono" />
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/60">{{ __('about.diagnostics.environment') }}</span>
                        <span class="text-base-content font-semibold uppercase">{{ $environment }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/60">{{ __('about.diagnostics.php_version') }}</span>
                        <span class="text-base-content font-mono">{{ $phpVersion }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/60">{{ __('about.diagnostics.laravel_version') }}</span>
                        <span class="text-base-content font-mono">{{ $laravelVersion }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-base-content/60">{{ __('about.diagnostics.license') }}</span>
                        <span class="text-base-content font-medium">{{ $license }}</span>
                    </div>

                    <div class="border-base-content/10 border-t pt-3">
                        <button
                            type="button"
                            @click="copy()"
                            class="border-base-content/20 hover:border-primary hover:text-primary text-base-content/80 bg-base-200/50 flex w-full items-center justify-center gap-2 rounded-xl border py-2.5 text-xs font-semibold transition-all"
                        >
                            <x-ts-icon name="clipboard-document" class="size-4" x-show="! copied" />
                            <x-ts-icon name="check" class="text-success size-4" x-show="copied" x-cloak />
                            <span x-text="copied ? '{{ __('about.diagnostics.copied_toast') }}' : '{{ __('about.diagnostics.copy_button') }}'"></span>
                        </button>
                    </div>
                </div>
            </x-ts-card>

            {{-- Open Source & Repository --}}
            <x-ts-card shadowless class="border-base-content/10 bg-base-100 border">
                <div class="space-y-3 p-1 text-xs">
                    <div class="text-primary flex items-center gap-2 font-bold">
                        <x-ts-icon name="code-bracket" class="size-4" />
                        <span>{{ __('about.license.heading') }}</span>
                    </div>
                    <p class="text-base-content/70 leading-relaxed">
                        {{ __('about.license.text', ['app' => $appName]) }}
                    </p>
                    <a
                        href="{{ $gitUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="text-primary inline-flex items-center gap-1.5 font-bold hover:underline"
                    >
                        <span>{{ __('about.license.view_repo') }}</span>
                        <x-ts-icon name="arrow-top-right-on-square" class="size-3.5" />
                    </a>
                </div>
            </x-ts-card>
        </div>
    </div>
</div>
