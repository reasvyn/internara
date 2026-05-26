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
                    <x-shared::widgets.empty-state
                        icon="o-shield-exclamation"
                        :title="__('dashboard.student.no_registration')"
                        :description="__('dashboard.student.no_registration_hint')"
                    />
                @endif
            </x-mary-card>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-shared::widgets.action-button :label="__('dashboard.student.write_journal')" icon="o-pencil-square" link="{{ route('student.logbook') }}" color="btn-primary" />
                <x-shared::widgets.action-button :label="__('dashboard.student.request_absence')" icon="o-document-plus" link="{{ route('student.attendance.absence') }}" color="bg-base-100 border border-base-content/10 hover:bg-base-200 text-base-content" />
                <x-shared::widgets.action-button :label="__('dashboard.student.my_documents')" icon="o-document-arrow-up" link="{{ route('registration.documents') }}" color="bg-base-100 border border-base-content/10 hover:bg-base-200 text-base-content" />
                <x-shared::widgets.action-button :label="__('dashboard.student.handbooks')" icon="o-book-open" link="{{ route('student.handbooks') }}" color="bg-base-100 border border-base-content/10 hover:bg-base-200 text-base-content" />
            </div>

            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title><span class="font-semibold">{{ __('dashboard.student.timeline') }}</span></x-slot:title>
                <x-shared::widgets.empty-state icon="o-queue-list" :title="__('dashboard.student.timeline_empty')" />
            </x-mary-card>
        </div>
    </div>
</div>
