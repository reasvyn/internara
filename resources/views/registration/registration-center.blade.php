<div>
    <x-mary-header :title="__('internship.registration_center.title')" :subtitle="__('internship.registration_center.subtitle')" separator />

    @if($this->openInternships->isEmpty())
        <div class="flex flex-col items-center justify-center py-24 text-center">
            <x-mary-icon name="o-x-circle" class="size-20 text-base-300 mb-6" />
            <h2 class="text-2xl font-black text-base-content/60 mb-2">{{ __('internship.registration_center.empty') }}</h2>
            <p class="text-base-content/40 max-w-md">
                {{ __('internship.registration_center.empty_desc') }}
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($this->openInternships as $internship)
                <x-mary-card class="border border-base-200 hover:shadow-lg transition-shadow">
                    <x-mary-badge value="{{ __('internship.statuses.' . $internship->status->value) }}" class="badge-info mb-3" />
                    <h3 class="text-lg font-bold mb-2">{{ $internship->name }}</h3>
                    <div class="text-sm text-base-content/60 space-y-1 mb-4">
                        <p>
                            <x-mary-icon name="o-calendar" class="size-4 inline" />
                            {{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}
                        </p>
                        @if($internship->registration_start_date || $internship->registration_end_date)
                            <p class="text-primary font-medium">
                                <x-mary-icon name="o-clock" class="size-4 inline" />
                                {{ __('internship.registration_center.title') }}: {{ $internship->registration_start_date?->format('d M Y') ?? '–' }} – {{ $internship->registration_end_date?->format('d M Y') ?? '–' }}
                            </p>
                        @endif
                    </div>

                    @auth
                        @role('student')
                            <x-mary-button
                                :label="__('internship.registration_center.register_now')"
                                icon-right="o-arrow-right"
                                class="btn-primary btn-sm w-full"
                                link="{{ route('student.internships.register') }}"
                                wire:navigate />
                        @else
                            <x-mary-button
                                :label="__('internship.registration_center.view_details')"
                                icon="o-eye"
                                class="btn-ghost btn-sm w-full"
                                disabled />
                        @endrole
                    @else
                        <x-mary-button
                            :label="__('internship.registration_center.register_guest')"
                            icon="o-user-plus"
                            class="btn-primary btn-sm w-full"
                            link="{{ route('apply') }}"
                            wire:navigate />
                    @endauth
                </x-mary-card>
            @endforeach
        </div>
    @endif
</div>
