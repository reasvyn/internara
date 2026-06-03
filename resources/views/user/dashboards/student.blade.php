<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.student.welcome', ['name' => auth()->user()->name])" separator />

    {{-- Stats / Empty Row --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @if($registration)
            <x-core::widgets.stat-card :title="__('dashboard.student.company')" :value="$registration->placement->company->name" icon="o-building-office" color="text-primary" />
            <x-core::widgets.stat-card :title="__('dashboard.student.position')" :value="$registration->placement->name" icon="o-briefcase" color="text-secondary" />
            <x-core::widgets.stat-card :title="__('dashboard.student.batch')" :value="$registration->internship->name" icon="o-academic-cap" color="text-accent" />
        @else
            <div class="sm:col-span-3">
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-core::widgets.empty-state icon="o-shield-exclamation" :title="__('dashboard.student.no_registration')" :description="__('dashboard.student.no_registration_hint')" />
                </x-mary-card>
            </div>
        @endif
    </div>

    {{-- Action Buttons + Journal Progress --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2">
            <div class="grid grid-cols-2 gap-4">
                <x-core::widgets.action-button :label="__('dashboard.student.write_journal')" icon="o-pencil-square" link="{{ route('student.logbook') }}" color="btn-primary" />
                <x-core::widgets.action-button :label="__('dashboard.student.request_absence')" icon="o-document-plus" link="{{ route('student.attendance.absence') }}" color="bg-base-100 border border-base-content/10 hover:bg-base-200 text-base-content" />
                <x-core::widgets.action-button :label="__('dashboard.student.my_documents')" icon="o-document-arrow-up" link="{{ route('registration.documents') }}" color="bg-base-100 border border-base-content/10 hover:bg-base-200 text-base-content" />
                <x-core::widgets.action-button :label="__('dashboard.student.handbooks')" icon="o-book-open" link="{{ route('student.handbooks') }}" color="bg-base-100 border border-base-content/10 hover:bg-base-200 text-base-content" />
            </div>
        </div>

        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center"><x-mary-icon name="o-check-badge" class="size-3.5" /></div>
                    <span class="font-semibold text-sm">{{ __('dashboard.student.journal_verification') }}</span>
                </div>
            </x-slot:title>
            <div class="text-center py-2">
                <span class="text-3xl font-bold tabular-nums text-primary">{{ $verifiedJournals }}/{{ max($totalJournals, 1) }}</span>
                <div class="h-2 bg-base-200 rounded-full overflow-hidden mt-3">
                    <div class="h-full rounded-full bg-success transition-all" style="width: {{ $totalJournals > 0 ? ($verifiedJournals / $totalJournals) * 100 : 0 }}%"></div>
                </div>
                <p class="text-xs text-base-content/40 mt-2">{{ __('dashboard.student.journal_hint') }}</p>
            </div>
        </x-mary-card>
    </div>

    {{-- Bottom Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2">
            <x-mary-card :title="__('dashboard.student.timeline')" separator>
                <x-core::widgets.empty-state icon="o-queue-list" :title="__('dashboard.student.timeline_empty')" />
            </x-mary-card>
        </div>

        <div class="space-y-4">
            <x-core::widgets.profile-summary :showEdit="true" />
            <x-mary-card :title="__('dashboard.quick_links')" separator>
                <div class="space-y-1">
                    <x-core::widgets.quick-link :label="__('dashboard.edit_profile')" icon="o-user" link="{{ route('profile') }}" />
                    <x-core::widgets.quick-link :label="__('profile.recovery.title')" icon="o-key" link="{{ route('profile.recovery') }}" />
                    <x-core::widgets.quick-link :label="__('dashboard.notifications')" icon="o-bell" link="{{ route('notifications') }}" />
                </div>
            </x-mary-card>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
