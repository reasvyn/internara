<div>
    <x-mary-header
        :title="__('dashboard.title')"
        :subtitle="__('dashboard.subtitle', ['name' => auth()->user()->name])"
        separator
    />

    {{-- Stats --}}
    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-5">
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.active_interns')"
            :value="$activeInterns"
            icon="users"
            color="text-primary"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.pending_evaluations')"
            :value="$pendingEvaluations"
            icon="star"
            color="text-warning"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.verified_journals')"
            :value="$verifiedJournals"
            icon="check-badge"
            color="text-success"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.supervisor.pending_journals')"
            :value="$pendingJournals"
            icon="book-open"
            color="text-error"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.supervisor.pending_attendance')"
            :value="$pendingAttendance"
            icon="clock"
            color="text-error"
        />
    </div>

    {{-- Main + Sidebar --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon name="clipboard-document-check" class="size-3.5" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.supervisor.verification_queue') }}</span>
                    </div>
                </x-slot:header>
                <x-core::widgets.empty-state
                    icon="clipboard-document-check"
                    :title="__('dashboard.supervisor.no_verifications')"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-core::widgets.action-button
                        :label="__('dashboard.supervisor.verify_logbooks')"
                        icon="pencil-square"
                        link="{{ route('sysadmin.logbook') }}"
                        color="btn-primary"
                    />
                    <x-core::widgets.action-button
                        :label="__('dashboard.supervisor.submit_evaluation')"
                        icon="star"
                        link="#"
                        color="btn-secondary"
                    />
                </div>
        </div>

        <div class="space-y-4">
            <x-core::widgets.profile-summary :showEdit="true" />
            <x-ts-card shadowless :header="__('dashboard.quick_links')">
                <div class="space-y-1">
                    <x-core::widgets.quick-link
                        :label="__('dashboard.edit_profile')"
                        icon="user"
                        link="{{ route('profile') }}"
                    />
                    <x-core::widgets.quick-link
                        :label="__('profile.recovery.title')"
                        icon="key"
                        link="{{ route('profile.recovery') }}"
                    />
                    <x-core::widgets.quick-link
                        :label="__('dashboard.notifications')"
                        icon="bell"
                        link="{{ route('notifications') }}"
                    />
                </div>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
