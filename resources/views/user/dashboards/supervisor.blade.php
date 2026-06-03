<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.subtitle', ['name' => auth()->user()->name])" separator />

    {{-- Stats --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-core::widgets.stat-card :title="__('dashboard.stats.active_interns')" :value="$activeInterns" icon="o-users" color="text-primary" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.pending_evaluations')" :value="$pendingEvaluations" icon="o-star" color="text-warning" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.verified_journals')" :value="$verifiedJournals" icon="o-check-badge" color="text-success" />
    </div>

    {{-- Main + Sidebar --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">
        <div class="lg:col-span-2 space-y-4">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center"><x-mary-icon name="o-clipboard-document-check" class="size-3.5" /></div>
                        <span class="font-semibold text-sm">{{ __('dashboard.supervisor.verification_queue') }}</span>
                    </div>
                </x-slot:title>
                <x-core::widgets.empty-state icon="o-clipboard-document-check" :title="__('dashboard.supervisor.no_verifications')" />
            </x-mary-card>

            <x-core::widgets.action-button :label="__('dashboard.read_docs')" icon="o-book-open" link="https://github.com/reasvyn/internara" color="btn-ghost bg-base-200/50" />
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
