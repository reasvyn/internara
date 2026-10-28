<div class="flex flex-1 flex-col">
    {{-- Hero Section --}}
    <section class="from-primary/8 via-base-100 to-primary/5 relative overflow-hidden bg-gradient-to-br">
        {{-- Animated blobs --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="bg-primary/10 absolute -top-24 -right-24 size-80 animate-pulse rounded-full blur-3xl sm:size-96 lg:size-[30rem]"></div>
            <div class="bg-primary/8 absolute -bottom-24 -left-24 size-80 animate-pulse rounded-full blur-3xl sm:size-96 lg:size-[30rem]"></div>
            <div class="bg-primary/5 absolute top-1/2 left-1/2 size-48 -translate-x-1/2 -translate-y-1/2 rounded-full blur-2xl sm:size-64"></div>
        </div>

        <div class="relative container mx-auto px-4 pt-10 pb-8 sm:px-6 sm:pt-14 sm:pb-12 lg:px-12 lg:pt-16 lg:pb-14">
            <div class="mx-auto max-w-3xl text-center">
                {{-- Brand --}}
                <div class="mb-4 flex justify-center sm:mb-6">
                    <x-ui::components.brand size="lg" :with-tagline="false" :invert="false" />
                </div>

                {{-- Tagline --}}
                <h1 class="from-base-content to-base-content/60 mb-3 bg-gradient-to-br bg-clip-text text-2xl font-black tracking-tight text-transparent sm:mb-4 sm:text-3xl lg:text-4xl">
                    {{ brand('tagline') ?: __('common.app_tagline') }}
                </h1>

                {{-- Description --}}
                <p class="text-base-content/65 mx-auto max-w-2xl text-xs leading-relaxed sm:text-sm lg:text-base">
                    {{ __('user.home.hero_desc') }}
                </p>
            </div>
        </div>

        {{-- Wave divider --}}
        <div class="relative h-8 sm:h-12" aria-hidden="true">
            <svg class="text-base-200 absolute bottom-0 h-8 w-full sm:h-12" viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 80C240 80 480 20 720 20C960 20 1200 80 1440 80V0H0V80Z" fill="currentColor" />
            </svg>
        </div>
    </section>

    {{-- Main Cards + Features Section --}}
    <section class="bg-base-200 flex-1 pb-10 sm:pb-14 lg:pb-16">
        <div class="relative z-10 container mx-auto -mt-4 px-4 sm:-mt-6 sm:px-6 lg:px-12">
            {{-- Registration + Login cards --}}
            <div class="mx-auto grid max-w-4xl grid-cols-1 gap-4 sm:gap-6 lg:grid-cols-2">
                {{-- Registration Card --}}
                <div class="group bg-base-100 border-base-content/10 hover:border-primary/30 flex flex-col justify-between rounded-lg border p-5 shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:p-6 lg:p-7">
                    <div class="flex w-full flex-col">
                        {{-- Card Header: Icon well + Status Badge --}}
                        <div class="flex w-full items-start justify-between gap-3">
                            <div class="from-primary/15 to-primary/5 ring-base-content/10 flex size-12 shrink-0 items-center justify-center rounded-md bg-gradient-to-br ring-1 transition-transform duration-500 ring-inset group-hover:scale-105 sm:size-14">
                                <x-ts-icon name="clipboard-document-list" class="text-primary size-6 sm:size-7" />
                            </div>

                            <div>
                                @if ($registration['status'] === 'open')
                                    <x-ts-badge
                                        color="success"
                                        round="sm"
                                        text="{{ __('user.home.registration_open') }}"
                                        icon="check-circle"
                                        class="px-2.5 py-1 text-xs font-semibold"
                                    />
                                @elseif ($registration['status'] === 'upcoming')
                                    <x-ts-badge
                                        color="info"
                                        round="sm"
                                        text="{{ __('user.home.registration_upcoming') }}"
                                        icon="clock"
                                        class="px-2.5 py-1 text-xs font-semibold"
                                    />
                                @elseif ($registration['status'] === 'closed')
                                    <x-ts-badge
                                        color="warning"
                                        round="sm"
                                        text="{{ __('user.home.registration_closed') }}"
                                        icon="x-circle"
                                        class="px-2.5 py-1 text-xs font-semibold"
                                    />
                                @else
                                    <x-ts-badge
                                        color="neutral"
                                        round="sm"
                                        text="{{ __('user.home.registration_unavailable') }}"
                                        icon="question-mark-circle"
                                        class="px-2.5 py-1 text-xs font-semibold"
                                    />
                                @endif
                            </div>
                        </div>

                        {{-- Card Heading & Description --}}
                        <div class="mt-3.5 w-full text-left sm:mt-4">
                            <h2 class="text-base-content text-base font-bold sm:text-lg lg:text-xl">
                                {{ __('user.home.registration_title') }}
                            </h2>
                            <p class="text-base-content/65 mt-1 text-xs leading-relaxed sm:text-sm">
                                {{ __('user.home.registration_desc') }}
                            </p>
                        </div>

                        {{-- Status Content / Schedule Capsule --}}
                        <div class="my-3.5 w-full sm:my-4">
                            @if ($registration['status'] === 'open')
                                <div class="border-base-content/10 bg-base-200/50 flex w-full items-center gap-2.5 rounded-md border px-3 py-2 text-left text-xs sm:px-3.5 sm:py-2.5">
                                    <x-ts-icon name="calendar-days" class="text-primary size-4 shrink-0 sm:size-4.5" />
                                    <p class="text-base-content/75 text-xs font-medium">
                                        {{
                                            __('user.home.registration_period', [
                                                'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                                'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                            ])
                                        }}
                                    </p>
                                </div>
                            @elseif ($registration['status'] === 'upcoming')
                                <div class="border-info/20 bg-info/5 flex w-full flex-col gap-1.5 rounded-md border p-3 text-left text-xs">
                                    <div class="flex items-center gap-2">
                                        <x-ts-icon name="clock" class="text-info size-4 shrink-0" />
                                        <span class="text-info font-semibold">
                                            {{
                                                __('user.home.registration_upcoming_period', [
                                                    'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                                    'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                                ])
                                            }}
                                        </span>
                                    </div>
                                    <p class="text-base-content/70 pl-6 text-xs">
                                        {{ __('user.home.registration_not_open_yet') }}
                                    </p>
                                </div>
                            @elseif ($registration['status'] === 'closed')
                                <div class="border-warning/20 bg-warning/5 flex w-full items-start gap-2.5 rounded-md border p-3 text-left text-xs">
                                    <x-ts-icon name="x-circle" class="text-warning mt-0.5 size-4 shrink-0" />
                                    <p class="text-base-content/75 text-xs leading-relaxed">
                                        {{ __('user.home.registration_closed_desc') }}
                                    </p>
                                </div>
                            @else
                                <div class="border-base-content/10 bg-base-200/50 flex w-full items-start gap-2.5 rounded-md border p-3 text-left text-xs">
                                    <x-ts-icon
                                        name="question-mark-circle"
                                        class="text-base-content/60 mt-0.5 size-4 shrink-0"
                                    />
                                    <p class="text-base-content/75 text-xs leading-relaxed">
                                        {{ __('user.home.registration_unavailable_desc') }}
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Action Row --}}
                    <div class="mt-auto w-full pt-1">
                        @if ($registration['status'] === 'open')
                            <x-ts-button
                                wire:navigate
                                href="{{ route('apply') }}"
                                :text="__('user.home.register_now')"
                                icon="arrow-right"
                                icon-right
                                color="primary"
                                class="w-full"
                            />
                        @else
                            <x-ts-button
                                wire:navigate
                                href="{{ route('login') }}"
                                :text="__('user.home.login_action')"
                                icon="arrow-right"
                                icon-right
                                color="primary"
                                class="w-full sm:hidden"
                            />
                        @endif
                    </div>
                </div>

                {{-- Login Card --}}
                <div class="group bg-base-100 border-base-content/10 hover:border-primary/30 flex flex-col justify-between rounded-lg border p-5 shadow-xs transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md sm:p-6 lg:p-7">
                    <div class="flex w-full flex-col">
                        {{-- Card Header: Icon well --}}
                        <div class="flex w-full items-start justify-between gap-3">
                            <div class="from-primary/15 to-primary/5 ring-base-content/10 flex size-12 shrink-0 items-center justify-center rounded-md bg-gradient-to-br ring-1 transition-transform duration-500 ring-inset group-hover:scale-105 sm:size-14">
                                <x-ts-icon name="arrow-right-on-rectangle" class="text-primary size-6 sm:size-7" />
                            </div>
                        </div>

                        {{-- Card Heading & Description --}}
                        <div class="mt-3.5 w-full text-left sm:mt-4">
                            <h2 class="text-base-content text-base font-bold sm:text-lg lg:text-xl">
                                {{ __('user.home.login_title') }}
                            </h2>
                            <p class="text-base-content/65 mt-1 text-xs leading-relaxed sm:text-sm">
                                {{ __('user.home.login_desc') }}
                            </p>
                        </div>
                    </div>

                    {{-- Action Row & Footer --}}
                    <div class="mt-auto w-full pt-4">
                        <x-ts-button
                            wire:navigate
                            href="{{ route('login') }}"
                            :text="__('user.home.login_action')"
                            icon="arrow-right"
                            icon-right
                            :color="$registration['status'] === 'open' ? 'secondary' : 'primary'"
                            :outline="$registration['status'] === 'open'"
                            class="w-full"
                        />

                        <div class="border-base-content/10 mt-3.5 w-full border-t pt-2.5 text-center sm:mt-4 sm:pt-3">
                            <p class="text-base-content/50 text-xs leading-relaxed">{{ __('user.home.no_account') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PKL Definition Callout --}}
            <div class="mx-auto mt-8 max-w-4xl sm:mt-10">
                <div class="bg-base-100 border-base-content/10 flex flex-col items-center gap-4 rounded-lg border p-5 text-center shadow-xs sm:flex-row sm:items-start sm:p-6 sm:text-left">
                    <div class="from-primary/15 to-primary/5 ring-base-content/10 flex size-12 shrink-0 items-center justify-center rounded-md bg-gradient-to-br ring-1 ring-inset sm:size-14">
                        <x-ts-icon name="academic-cap" class="text-primary size-6 sm:size-7" />
                    </div>
                    <div class="space-y-1.5">
                        <h2 class="text-base-content text-base font-bold sm:text-lg">
                            {{ __('user.home.pkl_definition_title') }}
                        </h2>
                        <p class="text-base-content/75 text-xs leading-relaxed sm:text-sm">
                            {{ __('user.home.pkl_definition_desc') }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Feature Highlights --}}
            <div class="mx-auto mt-10 max-w-4xl sm:mt-14">
                <div class="mb-5 text-center sm:mb-8">
                    <h2 class="text-base-content mb-1.5 text-lg font-bold sm:text-xl lg:text-2xl">
                        {{ __('user.home.features_title') }}
                    </h2>
                    <p class="text-base-content/55 mx-auto max-w-xl text-xs sm:text-sm">
                        {{ __('user.home.features_subtitle') }}
                    </p>
                </div>

                <div class="grid grid-cols-1 gap-3.5 sm:grid-cols-3 sm:gap-5">
                    {{-- Logbook --}}
                    <div class="bg-base-100 border-base-content/10 rounded-lg border p-4 text-center shadow-xs transition-all duration-200 hover:shadow-sm sm:p-5">
                        <div class="from-primary/10 to-primary/5 mx-auto mb-3 flex size-10 items-center justify-center rounded-md bg-gradient-to-br">
                            <x-ts-icon name="book-open" class="text-primary size-5" />
                        </div>
                        <h3 class="mb-1 text-sm font-semibold sm:text-base">
                            {{ __('user.home.feature_logbook_title') }}
                        </h3>
                        <p class="text-base-content/60 text-xs leading-relaxed">
                            {{ __('user.home.feature_logbook_desc') }}
                        </p>
                    </div>

                    {{-- Supervision --}}
                    <div class="bg-base-100 border-base-content/10 rounded-lg border p-4 text-center shadow-xs transition-all duration-200 hover:shadow-sm sm:p-5">
                        <div class="from-primary/10 to-primary/5 mx-auto mb-3 flex size-10 items-center justify-center rounded-md bg-gradient-to-br">
                            <x-ts-icon name="user-group" class="text-primary size-5" />
                        </div>
                        <h3 class="mb-1 text-sm font-semibold sm:text-base">
                            {{ __('user.home.feature_guidance_title') }}
                        </h3>
                        <p class="text-base-content/60 text-xs leading-relaxed">
                            {{ __('user.home.feature_guidance_desc') }}
                        </p>
                    </div>

                    {{-- Certificate --}}
                    <div class="bg-base-100 border-base-content/10 rounded-lg border p-4 text-center shadow-xs transition-all duration-200 hover:shadow-sm sm:p-5">
                        <div class="from-primary/10 to-primary/5 mx-auto mb-3 flex size-10 items-center justify-center rounded-md bg-gradient-to-br">
                            <x-ts-icon name="identification" class="text-primary size-5" />
                        </div>
                        <h3 class="mb-1 text-sm font-semibold sm:text-base">
                            {{ __('user.home.feature_certificate_title') }}
                        </h3>
                        <p class="text-base-content/60 text-xs leading-relaxed">
                            {{ __('user.home.feature_certificate_desc') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
