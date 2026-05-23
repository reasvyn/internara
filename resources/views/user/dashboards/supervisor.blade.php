<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold">{{ __('dashboard.title') }}</h2>
        <p class="text-sm text-base-content/50">{{ __('dashboard.subtitle', ['name' => auth()->user()->name]) }}</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-mary-stat
            :title="__('dashboard.stats.active_interns')"
            value="{{ $this->activeInterns }}"
            icon="o-users"
            class="bg-base-100 border border-base-content/10 rounded-xl"
            color="text-primary"
        />
        <x-mary-stat
            :title="__('dashboard.stats.pending_evaluations')"
            value="{{ $this->pendingEvaluations }}"
            icon="o-star"
            class="bg-base-100 border border-base-content/10 rounded-xl"
            color="text-warning"
        />
        <x-mary-stat
            :title="__('dashboard.stats.verified_journals')"
            value="{{ $this->verifiedJournals }}"
            icon="o-check-badge"
            class="bg-base-100 border border-base-content/10 rounded-xl"
            color="text-success"
        />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <span class="font-semibold">{{ __('dashboard.supervisor.verification_queue') }}</span>
                </x-slot:title>
                <div class="flex flex-col items-center justify-center py-12 text-base-content/20">
                    <x-mary-icon name="o-clipboard-document-check" class="size-12 mb-3" />
                    <span class="text-xs font-medium">{{ __('dashboard.supervisor.no_verifications') }}</span>
                </div>
            </x-mary-card>
        </div>

        <div class="flex flex-col gap-4">
            <x-mary-card class="bg-base-100 border border-base-content/10 text-center">
                <div class="flex flex-col items-center py-4">
                    <x-mary-avatar placeholder="{{ auth()->user()->initials() }}" class="!w-16 !h-16 mb-3" />
                    <h3 class="font-semibold">{{ auth()->user()->name }}</h3>
                    <p class="text-xs text-base-content/50 mt-0.5">{{ auth()->user()->getRoleNames()->first() }}</p>
                    <div class="w-full mt-4">
                        <x-mary-button :label="__('dashboard.profile.edit')" icon="o-user" class="btn-ghost btn-sm w-full" link="{{ route('profile') }}" />
                    </div>
                </div>
            </x-mary-card>

            <x-mary-button
                :label="__('dashboard.read_docs')"
                icon="o-book-open"
                class="btn-ghost bg-base-200/50 h-16 rounded-xl font-medium"
            />
        </div>
    </div>
</div>
