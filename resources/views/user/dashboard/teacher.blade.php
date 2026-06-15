<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.subtitle', ['name' => auth()->user()->name])" separator />

    {{-- Stats --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 mb-6">
        <x-core::widgets.stat-card :title="__('dashboard.stats.supervised_students')" :value="$supervisedStudents" icon="o-users" color="text-primary" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.pending_journals')" :value="$pendingJournals" icon="o-book-open" color="text-warning" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.active_companies')" :value="$activeCompanies" icon="o-building-office" color="text-secondary" />
        <x-core::widgets.stat-card :title="__('Ungraded Submissions')" :value="$ungradedSubmissions" icon="o-document-check" color="text-error" />
        <x-core::widgets.stat-card :title="__('Supervision Logs')" :value="$supervisionLogsCount" icon="o-check-badge" color="text-success" />
        <x-core::widgets.stat-card :title="__('Unresolved Incidents')" :value="$unresolvedIncidents" icon="o-shield-exclamation" color="text-error" />
    </div>

    {{-- Main + Sidebar --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 space-y-4">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center"><x-mary-icon name="o-clipboard-document-check" class="size-3.5" /></div>
                        <span class="font-semibold text-sm">{{ __('dashboard.teacher.recent_journals') }}</span>
                    </div>
                </x-slot:title>
                <x-core::widgets.empty-state icon="o-clipboard-document-check" :title="__('dashboard.teacher.no_journals')" />
            </x-mary-card>

            <div class="grid grid-cols-2 gap-4">
                <x-core::widgets.action-button :label="__('Verify Logbooks')" icon="o-pencil-square" link="{{ route('sysadmin.logbook') }}" color="btn-primary" />
                <x-core::widgets.action-button :label="__('Grade Assignments')" icon="o-document-check" link="{{ route('teacher.submissions.grading') }}" color="btn-secondary" />
                <x-core::widgets.action-button :label="__('Supervision Logs')" icon="o-check-badge" link="{{ route('supervision.logs') }}" color="btn-accent" />
            </div>
        </div>

        <div class="space-y-4">
            <x-core::widgets.profile-summary :showEdit="true" />
            <x-mary-card :title="__('dashboard.quick_links')" separator>
                <div class="space-y-1">
                    <x-core::widgets.quick-link :label="__('dashboard.edit_profile')" icon="o-user" link="{{ route('profile') }}" />
                    <x-core::widgets.quick-link :label="__('profile.recovery.title')" icon="o-key" link="{{ route('profile.recovery') }}" />
                    <x-core::widgets.quick-link :label="__('dashboard.notifications')" icon="o-bell" link="{{ route('notifications') }}" />
                    @if(auth()->user()?->hasRole('super_admin'))
                        <x-core::widgets.quick-link :label="__('dashboard.system_settings')" icon="o-cog-6-tooth" link="{{ route('admin.settings') }}" />
                    @endif
                </div>
            </x-mary-card>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
