<div>
    <x-ui::ui.page-header
        :title="__('dashboard.title')"
        :description="__('dashboard.subtitle', ['name' => auth()->user()->name])"
    />

    {{-- Stats --}}
    <div class="mb-6 grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        <x-ui::widgets.stat-card
            :title="__('dashboard.stats.supervised_students')"
            :value="$supervisedStudents"
            icon="users"
            color="text-primary"
        />
        <x-ui::widgets.stat-card
            :title="__('dashboard.stats.pending_journals')"
            :value="$pendingJournals"
            icon="book-open"
            color="text-warning"
        />
        <x-ui::widgets.stat-card
            :title="__('dashboard.stats.active_companies')"
            :value="$activeCompanies"
            icon="building-office"
            color="text-secondary"
        />
        <x-ui::widgets.stat-card
            :title="__('dashboard.teacher.ungraded_submissions')"
            :value="$ungradedSubmissions"
            icon="document-check"
            color="text-error"
        />
        <x-ui::widgets.stat-card
            :title="__('dashboard.teacher.supervision_logs')"
            :value="$supervisionLogsCount"
            icon="check-badge"
            color="text-success"
        />
        <x-ui::widgets.stat-card
            :title="__('dashboard.teacher.unresolved_incidents')"
            :value="$unresolvedIncidents"
            icon="shield-exclamation"
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
                        <span class="text-sm font-semibold">{{ __('dashboard.teacher.recent_journals') }}</span>
                    </div>
                </x-slot:header>
                <x-ui::widgets.empty-state
                    icon="clipboard-document-check"
                    :title="__('dashboard.teacher.no_journals')"
                />

                <div class="grid grid-cols-2 gap-4">
                    <x-ui::widgets.action-button
                        :label="__('dashboard.teacher.verify_logbooks')"
                        icon="pencil-square"
                        link="{{ route('sysadmin.logbook') }}"
                        color="primary"
                    />
                    <x-ui::widgets.action-button
                        :label="__('dashboard.teacher.grade_assignments')"
                        icon="document-check"
                        link="{{ route('teacher.submissions.grading') }}"
                        color="secondary"
                    />
                    <x-ui::widgets.action-button
                        :label="__('dashboard.teacher.supervision_logs')"
                        icon="check-badge"
                        link="{{ route('supervision.logs') }}"
                        color="accent"
                    />
                </div>
        </div>

        <div class="space-y-4">
            <x-ui::widgets.profile-summary :showEdit="true" />
            <x-ts-card shadowless :header="__('dashboard.quick_links')">
                <div class="space-y-1">
                    <x-ui::widgets.quick-link
                        :label="__('dashboard.edit_profile')"
                        icon="user"
                        link="{{ route('profile') }}"
                    />
                    <x-ui::widgets.quick-link
                        :label="__('profile.recovery.title')"
                        icon="key"
                        link="{{ route('profile.recovery') }}"
                    />
                    <x-ui::widgets.quick-link
                        :label="__('dashboard.notifications')"
                        icon="bell"
                        link="{{ route('notifications') }}"
                    />
                    @if (auth()->user()?->hasRole('super_admin'))
                        <x-ui::widgets.quick-link
                            :label="__('dashboard.system_settings')"
                            icon="cog-6-tooth"
                            link="{{ route('admin.settings') }}"
                        />
                    @endif
                </div>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
