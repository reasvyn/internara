<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('dashboard.title') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('dashboard.subtitle', ['name' => auth()->user()->name]) }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-shared::widgets.stat-card :title="__('dashboard.stats.supervised_students')" :value="$this->supervisedStudents" icon="o-users" color="text-primary" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.pending_journals')" :value="$this->pendingJournals" icon="o-book-open" color="text-warning" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.active_companies')" :value="$this->activeCompanies" icon="o-building-office" color="text-secondary" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title><span class="font-semibold">{{ __('dashboard.teacher.recent_journals') }}</span></x-slot:title>
                <x-shared::widgets.empty-state icon="o-clipboard-document-check" :title="__('dashboard.teacher.no_journals')" />
            </x-mary-card>
        </div>

        <div class="flex flex-col gap-4">
            <x-shared::widgets.profile-summary :showEdit="true" />
            <x-shared::widgets.action-button :label="__('dashboard.teacher.guidance_logs')" icon="o-clipboard-check" link="{{ route('supervision.logs') }}" color="btn-primary" />
        </div>
    </div>
</div>
