<div class="flex-1 flex flex-col">
    {{-- Hero Section --}}
    <section class="relative overflow-hidden bg-gradient-to-br from-primary/5 via-base-200 to-secondary/5">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-24 -right-24 size-96 rounded-full bg-primary/5 blur-3xl"></div>
            <div class="absolute -bottom-24 -left-24 size-96 rounded-full bg-secondary/5 blur-3xl"></div>
        </div>

        <div class="relative container mx-auto px-4 sm:px-6 lg:px-12 py-16 sm:py-20 lg:py-28">
            <div class="max-w-3xl mx-auto text-center">
                <div class="flex justify-center mb-6 sm:mb-8">
                    <x-core::ui.brand size="xl" :with-tagline="false" />
                </div>

                <h1 class="text-3xl sm:text-4xl lg:text-5xl font-black tracking-tight mb-4 bg-gradient-to-r from-base-content to-base-content/70 bg-clip-text">
                    {{ brand('tagline') ?: __('common.app_tagline') }}
                </h1>

                <p class="text-base sm:text-lg text-base-content/50 max-w-2xl mx-auto leading-relaxed">
                    {{ __('user.home.hero_desc') }}
                </p>

                <div class="mt-8 flex items-center justify-center gap-3 text-xs sm:text-sm text-base-content/30">
                    <span class="flex items-center gap-1.5">
                        <x-mary-icon name="o-shield-check" class="size-3.5" />
                        {{ __('user.home.hero_secure') }}
                    </span>
                    <span class="size-1 rounded-full bg-base-content/20"></span>
                    <span class="flex items-center gap-1.5">
                        <x-mary-icon name="o-academic-cap" class="size-3.5" />
                        {{ __('user.home.hero_academic') }}
                    </span>
                    <span class="size-1 rounded-full bg-base-content/20"></span>
                    <span class="flex items-center gap-1.5">
                        <x-mary-icon name="o-globe-alt" class="size-3.5" />
                        {{ __('user.home.hero_global') }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Decorative wave --}}
        <div class="relative h-16 sm:h-20">
            <svg class="absolute bottom-0 w-full h-16 sm:h-20 text-base-200" viewBox="0 0 1440 80" fill="none" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="none">
                <path d="M0 80C240 80 480 20 720 20C960 20 1200 80 1440 80V0H0V80Z" fill="currentColor"/>
            </svg>
        </div>
    </section>

    {{-- Cards Section --}}
    <section class="flex-1 bg-base-200 pb-16 sm:pb-20 lg:pb-24">
        <div class="container mx-auto px-4 sm:px-6 lg:px-12 -mt-8 sm:-mt-10 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 max-w-5xl mx-auto">
                {{-- Registration Card --}}
                <div class="group card bg-base-100 shadow-lg hover:shadow-xl border border-base-content/10 hover:border-primary/20 transition-all duration-300 hover:-translate-y-1">
                    <div class="card-body p-6 sm:p-8 lg:p-10 items-center text-center">
                        <div class="size-16 sm:size-20 rounded-2xl bg-gradient-to-br from-primary/10 to-primary/5 flex items-center justify-center mb-5 sm:mb-6 group-hover:scale-110 transition-transform duration-500">
                            <x-mary-icon name="o-clipboard-document-list" class="size-8 sm:size-10 text-primary" />
                        </div>

                        <h2 class="card-title text-xl sm:text-2xl lg:text-3xl font-bold mb-2">
                            {{ __('user.home.registration_title') }}
                        </h2>

                        <p class="text-sm sm:text-base text-base-content/60 mb-6 max-w-md leading-relaxed">
                            {{ __('user.home.registration_desc') }}
                        </p>

                        @if ($registration['status'] === 'open')
                            <div class="badge badge-success badge-lg gap-2 mb-4 px-4 py-3">
                                <x-mary-icon name="o-check-circle" class="size-4" />
                                {{ __('user.home.registration_open') }}
                            </div>
                            <p class="text-sm text-base-content/50 mb-6">
                                {{ __('user.home.registration_period', [
                                    'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                    'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                ]) }}
                            </p>
                            <a wire:navigate href="{{ route('apply') }}" class="btn btn-primary btn-lg w-full sm:w-auto gap-2">
                                {{ __('user.home.register_now') }}
                                <x-mary-icon name="o-arrow-right" class="size-4" />
                            </a>
                        @elseif ($registration['status'] === 'upcoming')
                            <div class="badge badge-info badge-lg gap-2 mb-4 px-4 py-3">
                                <x-mary-icon name="o-clock" class="size-4" />
                                {{ __('user.home.registration_upcoming') }}
                            </div>
                            <p class="text-sm text-base-content/50 mb-6">
                                {{ __('user.home.registration_upcoming_period', [
                                    'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                    'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                                ]) }}
                            </p>
                            <div class="alert alert-info bg-info/5 border-info/20 text-sm w-full">
                                <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                                <span>{{ __('user.home.registration_not_open_yet') }}</span>
                            </div>
                        @elseif ($registration['status'] === 'closed')
                            <div class="badge badge-warning badge-lg gap-2 mb-4 px-4 py-3">
                                <x-mary-icon name="o-x-circle" class="size-4" />
                                {{ __('user.home.registration_closed') }}
                            </div>
                            <div class="alert alert-warning bg-warning/5 border-warning/20 text-sm w-full">
                                <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                                <span>{{ __('user.home.registration_closed_desc') }}</span>
                            </div>
                        @else
                            <div class="badge badge-ghost badge-lg gap-2 mb-4 px-4 py-3">
                                <x-mary-icon name="o-question-mark-circle" class="size-4" />
                                {{ __('user.home.registration_unavailable') }}
                            </div>
                            <div class="alert bg-base-200 border-base-content/10 text-sm w-full">
                                <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                                <span>{{ __('user.home.registration_unavailable_desc') }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Login Card --}}
                <div class="group card bg-base-100 shadow-lg hover:shadow-xl border border-base-content/10 hover:border-secondary/20 transition-all duration-300 hover:-translate-y-1">
                    <div class="card-body p-6 sm:p-8 lg:p-10 items-center text-center">
                        <div class="size-16 sm:size-20 rounded-2xl bg-gradient-to-br from-secondary/10 to-secondary/5 flex items-center justify-center mb-5 sm:mb-6 group-hover:scale-110 transition-transform duration-500">
                            <x-mary-icon name="o-user" class="size-8 sm:size-10 text-secondary" />
                        </div>

                        <h2 class="card-title text-xl sm:text-2xl lg:text-3xl font-bold mb-2">
                            {{ __('user.home.login_title') }}
                        </h2>

                        <p class="text-sm sm:text-base text-base-content/60 mb-6 max-w-md leading-relaxed">
                            {{ __('user.home.login_desc') }}
                        </p>

                        <div class="flex flex-col gap-3 w-full sm:w-auto">
                            <a wire:navigate href="{{ route('login') }}" class="btn btn-secondary btn-lg gap-2">
                                {{ __('user.home.login_action') }}
                                <x-mary-icon name="o-arrow-right" class="size-4" />
                            </a>
                        </div>

                        <div class="mt-6 pt-6 border-t border-base-content/10 w-full">
                            <p class="text-xs text-base-content/40">
                                {{ __('user.home.no_account') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
