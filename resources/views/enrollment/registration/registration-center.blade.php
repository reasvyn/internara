<div>
    <x-ui::components.page-header
        :title="__('registration.center.title')"
        :description="__('registration.center.subtitle')"
    />

    @if ($this->openInternships->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <x-ts-icon name="x-circle" class="text-base-300 mb-6 size-20" />
            <h2 class="text-base-content/60 mb-2 text-2xl font-black">{{ __('registration.center.empty') }}</h2>
            <p class="text-base-content/40 max-w-md">{{ __('registration.center.empty_desc') }}</p>
        </div>
    @else
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach ($this->openInternships as $internship)
                <x-ts-card class="border-base-200 border transition-shadow hover:shadow-lg">
                    <x-ts-badge
                        :text="__('internship.statuses.'.$internship->status->value)"
                        color="blue"
                        class="mb-3"
                    />
                    <h3 class="mb-2 text-lg font-bold">{{ $internship->name }}</h3>
                    <div class="text-base-content/60 mb-4 space-y-1 text-sm">
                        <p>
                            <x-ts-icon name="calendar" class="inline size-4" />
                            {{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}
                        </p>
                        @if ($internship->registration_start_date || $internship->registration_end_date)
                            <p class="text-primary font-medium">
                                <x-ts-icon name="clock" class="inline size-4" />
                                {{ __('registration.center.title') }}: {{ $internship->registration_start_date?->format('d M Y') ?? '–' }} – {{ $internship->registration_end_date?->format('d M Y') ?? '–' }}
                            </p>
                        @endif
                    </div>

                    @auth
                        @role('student')
                            <x-ts-button
                                :text="__('registration.center.register_now')"
                                icon-right="arrow-right"
                                class="w-full"
                                color="primary"
                                sm
                                href="{{ route('registration.wizard') }}"
                                wire:navigate
                            />
                        @else
                            <x-ts-button
                                :text="__('registration.center.view_details')"
                                icon="eye"
                                class="w-full"
                                color="slate"
                                outline
                                sm
                                disabled
                            />
                        @endrole
                    @else
                        <x-ts-button
                            :text="__('registration.center.register_guest')"
                            icon="user-plus"
                            class="w-full"
                            color="primary"
                            sm
                            href="{{ route('apply') }}"
                            wire:navigate
                        />
                    @endauth
                </x-ts-card>

            @endforeach
        </div>
    @endif
</div>
