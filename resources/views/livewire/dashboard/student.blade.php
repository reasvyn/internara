<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('dashboard.title') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('dashboard.student.welcome', ['name' => auth()->user()->name]) }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="space-y-6">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                @if($registration)
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <x-mary-icon name="o-building-office" class="size-10 text-base-content/30" />
                            <div>
                                <p class="text-xs text-base-content/50">{{ __('dashboard.student.company') }}</p>
                                <p class="font-semibold text-sm">{{ $registration->placement->company->name }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="px-4 py-3 rounded-lg bg-base-200/30 border border-base-content/10">
                                <p class="text-xs text-base-content/50">{{ __('dashboard.student.position') }}</p>
                                <p class="text-sm font-medium">{{ $registration->placement->name }}</p>
                            </div>
                            <div class="px-4 py-3 rounded-lg bg-base-200/30 border border-base-content/10">
                                <p class="text-xs text-base-content/50">{{ __('dashboard.student.batch') }}</p>
                                <p class="text-sm font-medium">{{ $registration->internship->name }}</p>
                            </div>
                        </div>

                        <div class="pt-4 border-t border-base-content/10">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs text-base-content/50">{{ __('dashboard.student.journal_verification') }}</span>
                                <span class="text-xs font-medium">{{ $verifiedJournals }}/{{ $totalJournals }}</span>
                            </div>
                            <progress class="progress progress-primary h-1.5 w-full rounded" value="{{ $totalJournals > 0 ? ($verifiedJournals / $totalJournals) * 100 : 0 }}" max="100"></progress>
                            <p class="text-xs text-base-content/40 mt-2">{{ __('dashboard.student.journal_hint') }}</p>
                        </div>
                    </div>
                @else
                    <div class="text-center py-8">
                        <x-mary-icon name="o-shield-exclamation" class="size-10 text-base-content/20 mx-auto mb-3" />
                        <p class="text-sm font-medium">{{ __('dashboard.student.no_registration') }}</p>
                        <p class="text-xs text-base-content/40 mt-1">{{ __('dashboard.student.no_registration_hint') }}</p>
                    </div>
                @endif
            </x-mary-card>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-mary-button
                    :label="__('dashboard.student.write_journal')"
                    icon="o-pencil-square"
                    class="btn-primary h-20 rounded-xl font-medium shadow-none"
                    link="{{ route('student.logbook') }}"
                    wire:navigate
                />
                <x-mary-button
                    :label="__('dashboard.student.request_absence')"
                    icon="o-document-plus"
                    class="bg-base-100 border border-base-content/10 hover:bg-base-200 h-20 rounded-xl font-medium shadow-none"
                    link="{{ route('student.attendance.absence') }}"
                    wire:navigate
                />
                <x-mary-button
                    :label="__('dashboard.student.my_documents')"
                    icon="o-document-arrow-up"
                    class="bg-base-100 border border-base-content/10 hover:bg-base-200 h-20 rounded-xl font-medium shadow-none"
                    link="{{ route('student.documents') }}"
                    wire:navigate
                />
                <x-mary-button
                    :label="__('dashboard.student.handbooks')"
                    icon="o-book-open"
                    class="bg-base-100 border border-base-content/10 hover:bg-base-200 h-20 rounded-xl font-medium shadow-none"
                    link="{{ route('student.handbooks') }}"
                    wire:navigate
                />
            </div>

            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <span class="font-semibold">{{ __('dashboard.student.timeline') }}</span>
                </x-slot:title>
                <div class="flex flex-col items-center justify-center py-12 text-base-content/20">
                    <x-mary-icon name="o-queue-list" class="size-12 mb-3" />
                    <span class="text-xs font-medium">{{ __('dashboard.student.timeline_empty') }}</span>
                </div>
            </x-mary-card>
        </div>
    </div>
</div>
