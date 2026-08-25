<div class="flex flex-1 flex-col">
    {{-- Hero Section --}}
    <section class="from-primary/8 via-base-100 to-secondary/8 relative overflow-hidden bg-gradient-to-br">
        {{-- Animated blobs --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="bg-primary/10 absolute -top-32 -right-32 size-[36rem] animate-pulse rounded-full blur-3xl"></div>
            <div
                class="bg-secondary/10 absolute -bottom-32 -left-32 size-[36rem] animate-pulse rounded-full blur-3xl"
                style="animation-delay: 1.5s"
            ></div>
            <div class="bg-accent/5 absolute top-1/2 left-1/2 size-64 -translate-x-1/2 -translate-y-1/2 rounded-full blur-2xl"></div>
        </div>

        <div class="relative container mx-auto px-4 pt-20 pb-16 sm:px-6 sm:pt-24 sm:pb-20 lg:px-12 lg:pt-32 lg:pb-24">
            <div class="mx-auto max-w-3xl text-center">
                {{-- Brand --}}
                <div class="mb-8 flex justify-center">
                    <x-core::ui.brand size="xl" :with-tagline="false" />
                </div>

                {{-- Feature pills --}}
                <div class="mb-6 flex flex-wrap items-center justify-center gap-2">
                    <x-ts-badge color="primary" text="{{ __('user.home.hero_secure') }}" icon="shield-check" />
                    <x-ts-badge color="secondary" text="{{ __('user.home.hero_academic') }}" icon="academic-cap" />
                    <x-ts-badge color="success" text="{{ __('user.home.hero_global') }}" icon="globe-alt" />
                </div>

                {{-- Tagline --}}
                <h1
                    class="from-base-content to-base-content/60 mb-5 bg-gradient-to-br bg-clip-text text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl"
                    tabindex="-1"
                >
                    {{ brand('tagline') ?: __('common.app_tagline') }}
                </h1>

                <p class="text-base-content/60 mx-auto max-w-xl text-base leading-relaxed sm:text-lg">
                    {{ __('user.home.hero_desc') }}
                </p>
            </div>
        </div>

        {{-- Wave divider --}}
        <div class="relative h-16 sm:h-20" aria-hidden="true">
            <svg class="text-base-200 absolute bottom-0 h-16 w-full sm:h-20" viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 80C240 80 480 20 720 20C960 20 1200 80 1440 80V0H0V80Z" fill="currentColor" />
            </svg>
        </div>
    </section>

    {{-- Main Cards + Features Section --}}
    <section class="bg-base-200 flex-1 pb-16 sm:pb-20 lg:pb-24">
        <div class="relative z-10 container mx-auto -mt-8 px-4 sm:-mt-10 sm:px-6 lg:px-12">
            {{-- Registration + Login cards --}}
            <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-2">
                {{-- Registration Card --}}
                <div class="group card bg-base-100 border-base-content/10 hover:border-primary/30 border shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="card-body items-center p-6 text-center sm:p-8 lg:p-10">
                        <div class="from-primary/15 to-primary/5 mb-5 flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br ring-1 ring-white/10 transition-transform duration-500 ring-inset group-hover:scale-110 sm:mb-6 sm:size-20">
                            <x-ts-icon name="clipboard-document-list" class="text-primary size-8 sm:size-10" />
                        </div>

                        <h2 class="card-title mb-2 text-xl font-bold sm:text-2xl">
                            {{ __('user.home.registration_title') }}
                        </h2>

                        <p class="text-base-content/60 mb-5 max-w-sm text-sm leading-relaxed sm:text-base">
                            {{ __('user.home.registration_desc') }}
                        </p>

                        @if ($registration['status'] === 'open')
                            <x-ts-badge
                                color="success"
                                text="{{ __('user.home.registration_open') }}"
                                icon="check-circle"
                                class="mb-3 px-4 py-2.5 text-sm"
                            />
                            <p class="text-base-content/50 mb-5 text-sm">
                                {{
                                    __('user.home.registration_period', [
                                        'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                        'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                    ])
                                }}
                            </p>
                            <x-ts-button
                                wire:navigate
                                href="{{ route('apply') }}"
                                :text="__('user.home.register_now')"
                                icon="arrow-right"
                                icon-right
                                color="primary"
                                class="w-full sm:w-auto"
                            />
                        @elseif ($registration['status'] === 'upcoming')
                            <x-ts-badge
                                color="info"
                                text="{{ __('user.home.registration_upcoming') }}"
                                icon="clock"
                                class="mb-3 px-4 py-2.5 text-sm"
                            />
                            <p class="text-base-content/50 mb-5 text-sm">
                                {{
                                    __('user.home.registration_upcoming_period', [
                                        'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                        'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                    ])
                                }}
                            </p>
                            <x-ts-alert
                                color="info"
                                :text="__('user.home.registration_not_open_yet')"
                                class="w-full text-sm"
                            />
                        @elseif ($registration['status'] === 'closed')
                            <x-ts-badge
                                color="warning"
                                text="{{ __('user.home.registration_closed') }}"
                                icon="x-circle"
                                class="mb-3 px-4 py-2.5 text-sm"
                            />
                            <x-ts-alert
                                color="warning"
                                :text="__('user.home.registration_closed_desc')"
                                class="w-full text-sm"
                            />
                        @else
                            <x-ts-badge
                                text="{{ __('user.home.registration_unavailable') }}"
                                icon="question-mark-circle"
                                class="mb-3 px-4 py-2.5 text-sm"
                            />
                            <x-ts-alert :text="__('user.home.registration_unavailable_desc')" class="w-full text-sm" />
                        @endif
                    </div>
                </div>

                {{-- Login Card --}}
                <div class="group card bg-base-100 border-base-content/10 hover:border-secondary/30 border shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="card-body items-center p-6 text-center sm:p-8 lg:p-10">
                        <div class="from-secondary/15 to-secondary/5 mb-5 flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br ring-1 ring-white/10 transition-transform duration-500 ring-inset group-hover:scale-110 sm:mb-6 sm:size-20">
                            <x-ts-icon name="user-circle" class="text-secondary size-8 sm:size-10" />
                        </div>

                        <h2 class="card-title mb-2 text-xl font-bold sm:text-2xl">{{ __('user.home.login_title') }}</h2>

                        <p class="text-base-content/60 mb-6 max-w-sm text-sm leading-relaxed sm:text-base">
                            {{ __('user.home.login_desc') }}
                        </p>

                        <x-ts-button
                            wire:navigate
                            href="{{ route('login') }}"
                            :text="__('user.home.login_action')"
                            icon="arrow-right"
                            icon-right
                            color="secondary"
                            class="w-full sm:w-auto"
                        />

                        <div class="border-base-content/10 mt-6 w-full border-t pt-5">
                            <p class="text-base-content/40 text-xs">{{ __('user.home.no_account') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Feature Highlights --}}
            <div class="mx-auto mt-16 max-w-5xl sm:mt-20">
                <div class="mb-8 text-center sm:mb-12">
                    <h2 class="text-base-content mb-2 text-xl font-bold sm:text-2xl lg:text-3xl">
                        {{ __('user.home.features_title') }}
                    </h2>
                    <p class="text-base-content/50 mx-auto max-w-2xl text-sm sm:text-base">
                        {{ __('user.home.features_subtitle') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 sm:gap-6">
                    {{-- Logbook --}}
                    <div class="card bg-base-100 border-base-content/10 border shadow-sm transition-all duration-200 hover:shadow-md">
                        <div class="card-body p-6 text-center sm:p-8">
                            <div class="from-primary/10 to-primary/5 mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-gradient-to-br">
                                <x-ts-icon name="book-open" class="text-primary size-6" />
                            </div>
                            <h3 class="mb-1.5 font-semibold">{{ __('user.home.feature_logbook_title') }}</h3>
                            <p class="text-base-content/55 text-sm leading-relaxed">
                                {{ __('user.home.feature_logbook_desc') }}
                            </p>
                        </div>
                    </div>

                    {{-- Supervision --}}
                    <div class="card bg-base-100 border-base-content/10 border shadow-sm transition-all duration-200 hover:shadow-md">
                        <div class="card-body p-6 text-center sm:p-8">
                            <div class="from-secondary/10 to-secondary/5 mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-gradient-to-br">
                                <x-ts-icon name="users" class="text-secondary size-6" />
                            </div>
                            <h3 class="mb-1.5 font-semibold">{{ __('user.home.feature_guidance_title') }}</h3>
                            <p class="text-base-content/55 text-sm leading-relaxed">
                                {{ __('user.home.feature_guidance_desc') }}
                            </p>
                        </div>
                    </div>

                    {{-- Certificate --}}
                    <div class="card bg-base-100 border-base-content/10 border shadow-sm transition-all duration-200 hover:shadow-md">
                        <div class="card-body p-6 text-center sm:p-8">
                            <div class="from-accent/10 to-accent/5 mx-auto mb-4 flex size-12 items-center justify-center rounded-xl bg-gradient-to-br">
                                <x-ts-icon name="identification" class="text-accent size-6" />
                            </div>
                            <h3 class="mb-1.5 font-semibold">{{ __('user.home.feature_certificate_title') }}</h3>
                            <p class="text-base-content/55 text-sm leading-relaxed">
                                {{ __('user.home.feature_certificate_desc') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
