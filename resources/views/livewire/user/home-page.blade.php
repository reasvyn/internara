<div class="flex-1 flex items-center justify-center">
    <div class="container mx-auto px-4 sm:px-6 lg:px-12 py-8 sm:py-12 lg:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 sm:gap-8 lg:gap-12 max-w-5xl mx-auto">

            {{-- Left Column: Registration CTA --}}
            <div class="card bg-base-100 shadow-xl border border-base-content/10">
                <div class="card-body p-6 sm:p-8 lg:p-10 items-center text-center">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-primary/10 flex items-center justify-center mb-4 sm:mb-6">
                        <x-mary-icon name="o-clipboard-document-list" class="size-8 sm:size-10 text-primary" />
                    </div>

                    <h2 class="card-title text-xl sm:text-2xl lg:text-3xl font-bold mb-2">
                        {{ __('user.home.registration_title') }}
                    </h2>

                    <p class="text-sm sm:text-base text-base-content/70 mb-4 sm:mb-6 max-w-md">
                        {{ __('user.home.registration_desc') }}
                    </p>

                    @if ($registration['status'] === 'open')
                        <div class="badge badge-success badge-lg gap-2 mb-4">
                            <x-mary-icon name="o-check-circle" class="size-4" />
                            {{ __('user.home.registration_open') }}
                        </div>
                        <p class="text-sm text-base-content/60 mb-6">
                            {{ __('user.home.registration_period', [
                                'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                            ]) }}
                        </p>
                        <a wire:navigate href="{{ route('apply') }}" class="btn btn-primary btn-lg w-full sm:w-auto">
                            <x-mary-icon name="o-arrow-right" class="size-5" />
                            {{ __('user.home.register_now') }}
                        </a>

                    @elseif ($registration['status'] === 'upcoming')
                        <div class="badge badge-info badge-lg gap-2 mb-4">
                            <x-mary-icon name="o-clock" class="size-4" />
                            {{ __('user.home.registration_upcoming') }}
                        </div>
                        <p class="text-sm text-base-content/60 mb-6">
                            {{ __('user.home.registration_upcoming_period', [
                                'start' => \Carbon\Carbon::parse($registration['start_date'])->translatedFormat('j F Y'),
                                'end' => \Carbon\Carbon::parse($registration['end_date'])->translatedFormat('j F Y'),
                            ]) }}
                        </p>
                        <div class="alert alert-info bg-info/5 border-info/20 text-sm">
                            <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                            <span>{{ __('user.home.registration_not_open_yet') }}</span>
                        </div>

                    @elseif ($registration['status'] === 'closed')
                        <div class="badge badge-warning badge-lg gap-2 mb-4">
                            <x-mary-icon name="o-x-circle" class="size-4" />
                            {{ __('user.home.registration_closed') }}
                        </div>
                        <div class="alert alert-warning bg-warning/5 border-warning/20 text-sm">
                            <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                            <span>{{ __('user.home.registration_closed_desc') }}</span>
                        </div>

                    @else
                        <div class="badge badge-ghost badge-lg gap-2 mb-4">
                            <x-mary-icon name="o-question-mark-circle" class="size-4" />
                            {{ __('user.home.registration_unavailable') }}
                        </div>
                        <div class="alert bg-base-200 border-base-content/10 text-sm">
                            <x-mary-icon name="o-information-circle" class="size-5 shrink-0" />
                            <span>{{ __('user.home.registration_unavailable_desc') }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Right Column: Login CTA --}}
            <div class="card bg-base-100 shadow-xl border border-base-content/10">
                <div class="card-body p-6 sm:p-8 lg:p-10 items-center text-center">
                    <div class="w-16 h-16 sm:w-20 sm:h-20 rounded-full bg-secondary/10 flex items-center justify-center mb-4 sm:mb-6">
                        <x-mary-icon name="o-user" class="size-8 sm:size-10 text-secondary" />
                    </div>

                    <h2 class="card-title text-xl sm:text-2xl lg:text-3xl font-bold mb-2">
                        {{ __('user.home.login_title') }}
                    </h2>

                    <p class="text-sm sm:text-base text-base-content/70 mb-4 sm:mb-6 max-w-md">
                        {{ __('user.home.login_desc') }}
                    </p>

                    <a wire:navigate href="{{ route('login') }}" class="btn btn-secondary btn-lg w-full sm:w-auto">
                        <x-mary-icon name="o-arrow-right" class="size-5" />
                        {{ __('user.home.login_action') }}
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
