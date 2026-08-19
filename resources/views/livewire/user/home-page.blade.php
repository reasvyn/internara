<div class="flex flex-1 flex-col">
    {{-- Hero Section --}}
    <section class="from-primary/5 via-base-200 to-secondary/5 relative overflow-hidden bg-gradient-to-br">
        <div class="pointer-events-none absolute inset-0">
            <div class="bg-primary/5 absolute -top-24 -right-24 size-96 rounded-full blur-3xl"></div>
            <div class="bg-secondary/5 absolute -bottom-24 -left-24 size-96 rounded-full blur-3xl"></div>
        </div>

        <div class="relative container mx-auto px-4 py-16 sm:px-6 sm:py-20 lg:px-12 lg:py-28">
            <div class="mx-auto max-w-3xl text-center">
                <div class="mb-6 flex justify-center sm:mb-8">
                    <x-core::ui.brand size="xl" :with-tagline="false" />
                </div>

                <h1 class="from-base-content to-base-content/70 mb-4 bg-gradient-to-r bg-clip-text text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl">
                    {{ brand('tagline') ?: __('common.app_tagline') }}
                </h1>

                <p class="text-base-content/50 mx-auto max-w-2xl text-base leading-relaxed sm:text-lg">
                    {{ __('user.home.hero_desc') }}
                </p>

                <div class="text-base-content/60 mt-8 flex items-center justify-center gap-3 text-xs sm:text-sm">
                    <span class="flex items-center gap-1.5">
                        <x-mary-icon name="o-shield-check" class="size-3.5" />
                        {{ __('user.home.hero_secure') }}
                    </span>
                    <span class="bg-base-content/20 size-1 rounded-full"></span>
                    <span class="flex items-center gap-1.5">
                        <x-mary-icon name="o-academic-cap" class="size-3.5" />
                        {{ __('user.home.hero_academic') }}
                    </span>
                    <span class="bg-base-content/20 size-1 rounded-full"></span>
                    <span class="flex items-center gap-1.5">
                        <x-mary-icon name="o-globe-alt" class="size-3.5" />
                        {{ __('user.home.hero_global') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Decorative wave --}}
        <div class="relative h-16 sm:h-20">
            <svg class="text-base-200 absolute bottom-0 h-16 w-full sm:h-20" viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none" aria-hidden="true">
                <path d="M0 80C240 80 480 20 720 20C960 20 1200 80 1440 80V0H0V80Z" fill="currentColor" />
            </svg>
        </div>
    </section>

    {{-- Cards Section --}}
    <section class="bg-base-200 flex-1 pb-16 sm:pb-20 lg:pb-24">
        <div class="relative z-10 container mx-auto -mt-8 px-4 sm:-mt-10 sm:px-6 lg:px-12">
            <div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 sm:gap-8 lg:grid-cols-2">
                {{-- Registration Card --}}
                <div class="group card bg-base-100 border-base-content/10 hover:border-primary/20 border shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="card-body items-center p-6 text-center sm:p-8 lg:p-10">
                        <div class="from-primary/10 to-primary/5 mb-5 flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br transition-transform duration-500 group-hover:scale-110 sm:mb-6 sm:size-20">
                            <x-mary-icon name="o-clipboard-document-list" class="text-primary size-8 sm:size-10" />
                        </div>

                        <h2 class="card-title mb-2 text-xl font-bold sm:text-2xl lg:text-3xl">
                            {{ __('user.home.registration_title') }}
                        </h2>

                        <p class="text-base-content/60 mb-6 max-w-md text-sm leading-relaxed sm:text-base">
                            {{ __('user.home.registration_desc') }}
                        </p>

                        @if ($registration['status'] === 'open')
                            <div class="badge badge-success badge-lg mb-4 gap-2 px-4 py-3">
                                <x-mary-icon name="o-check-circle" class="size-4" />
                                {{ __('user.home.registration_open') }}
                            </div>
                            <p class="text-base-content/50 mb-6 text-sm">
                                {{
                                    __('user.home.registration_period', [
                                        'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                        'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                    ])
                                }}
                            </p>
                            <a
                                wire:navigate
                                href="{{ route('apply') }}"
                                class="btn btn-primary btn-lg w-full gap-2 sm:w-auto"
                            >
                                {{ __('user.home.register_now') }}
                                <x-mary-icon name="o-arrow-right" class="size-4" />
                            </a>
                        @elseif ($registration['status'] === 'upcoming')
                            <div class="badge badge-info badge-lg mb-4 gap-2 px-4 py-3">
                                <x-mary-icon name="o-clock" class="size-4" />
                                {{ __('user.home.registration_upcoming') }}
                            </div>
                            <p class="text-base-content/50 mb-6 text-sm">
                                {{
                                    __('user.home.registration_upcoming_period', [
                                        'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                        'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                    ])
                                }}
                            </p>
                            <div class="alert alert-info bg-info/5 border-info/20 w-full text-sm">
                                <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                                <span>{{ __('user.home.registration_not_open_yet') }}</span>
                            </div>
                        @elseif ($registration['status'] === 'closed')
                            <div class="badge badge-warning badge-lg mb-4 gap-2 px-4 py-3">
                                <x-mary-icon name="o-x-circle" class="size-4" />
                                {{ __('user.home.registration_closed') }}
                            </div>
                            <div class="alert alert-warning bg-warning/5 border-warning/20 w-full text-sm">
                                <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                                <span>{{ __('user.home.registration_closed_desc') }}</span>
                            </div>
                        @else
                            <div class="badge badge-ghost badge-lg mb-4 gap-2 px-4 py-3">
                                <x-mary-icon name="o-question-mark-circle" class="size-4" />
                                {{ __('user.home.registration_unavailable') }}
                            </div>
                            <div class="alert bg-base-200 border-base-content/10 w-full text-sm">
                                <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                                <span>{{ __('user.home.registration_unavailable_desc') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Login Card --}}
                <div class="group card bg-base-100 border-base-content/10 hover:border-secondary/20 border shadow-lg transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                    <div class="card-body items-center p-6 text-center sm:p-8 lg:p-10">
                        <div class="from-secondary/10 to-secondary/5 mb-5 flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br transition-transform duration-500 group-hover:scale-110 sm:mb-6 sm:size-20">
                            <x-mary-icon name="o-user" class="text-secondary size-8 sm:size-10" />
                        </div>

                        <h2 class="card-title mb-2 text-xl font-bold sm:text-2xl lg:text-3xl">
                            {{ __('user.home.login_title') }}
                        </h2>

                        <p class="text-base-content/60 mb-6 max-w-md text-sm leading-relaxed sm:text-base">
                            {{ __('user.home.login_desc') }}
                        </p>

                        <div class="flex w-full flex-col gap-3 sm:w-auto">
                            <a wire:navigate href="{{ route('login') }}" class="btn btn-secondary btn-lg gap-2">
                                {{ __('user.home.login_action') }}
                                <x-mary-icon name="o-arrow-right" class="size-4" />
                            </a>
                        </div>

                        <div class="border-base-content/10 mt-6 w-full border-t pt-6">
                            <p class="text-base-content/40 text-xs">{{ __('user.home.no_account') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
