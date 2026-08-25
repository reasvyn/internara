<div>
    <x-core::ui.page-header
        :title="__('dashboard.title')"
        :description="__('dashboard.subtitle', ['name' => auth()->user()->name])"
    />

    {{-- People Overview --}}
    <div class="mb-6 grid grid-cols-3 gap-3 sm:grid-cols-6">
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.total_students')"
            :value="$stats['totalStudents']"
            icon="users"
            color="text-primary"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.instructors')"
            :value="$stats['totalTeachers']"
            icon="academic-cap"
            color="text-secondary"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.supervisors')"
            :value="$stats['totalSupervisors']"
            icon="briefcase"
            color="text-accent"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.departments')"
            :value="$stats['totalDepartments']"
            icon="building-library"
            color="text-primary"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.companies')"
            :value="$stats['totalCompanies']"
            icon="building-office"
            color="text-secondary"
        />
        <x-core::widgets.stat-card
            :title="__('dashboard.stats.internships')"
            :value="$stats['activeInternships']"
            :suffix="__('dashboard.stats.active')"
            icon="flag"
            color="text-info"
        />
    </div>

    {{-- PKL Funnel --}}
    <div class="bg-base-100 border-base-content/10 mb-6 rounded-xl border p-5">
        <div class="mb-5 flex items-start gap-3">
            <div class="bg-primary/10 text-primary flex size-9 shrink-0 items-center justify-center rounded-lg">
                <x-ts-icon class="size-4" name="funnel" />
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold">{{ __('dashboard.pipeline.title') }}</h3>
                        <p class="text-base-content/50 mt-0.5 text-xs">{{ __('dashboard.pipeline.subtitle') }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <span class="text-base-content/40 block text-xs">{{ __('dashboard.pipeline.throughput') }}</span>
                        <span class="{{ $throughputClass }} text-lg font-bold tabular-nums">{{ $pipelineThroughput }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-0.5">
            @foreach ($pipelineStages as $stage)
                <div class="flex items-center gap-3 py-2">
                    <div class="w-20 shrink-0 text-right">
                        <span class="text-base-content/70 text-xs font-medium">{{ $stage['label'] }}</span>
                    </div>
                    <div class="bg-base-200/50 relative h-7 flex-1 overflow-hidden rounded-md">
                        <div
                            class="{{ $stage['c'] }} h-full rounded-md transition-all duration-700"
                            style="width: {{ $stage['width'] }}%"
                        ></div>
                        <span class="{{ $stage['barTextClass'] }} absolute inset-0 flex items-center px-2 text-xs font-bold tabular-nums">{{ $stage['v'] }}</span>
                    </div>
                    <div class="{{ $stage['dropClass'] }} w-14 shrink-0 text-left text-xs">
                        {{ $stage['dropLabel'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="border-base-content/10 mt-4 grid grid-cols-3 gap-4 border-t pt-4">
            <div class="text-center">
                <span class="text-primary text-lg font-bold tabular-nums">{{ $pipelineAbsorption }}%</span>
                <p class="text-base-content/50 mt-0.5 text-[10px]">{{ __('dashboard.pipeline.absorption') }}</p>
            </div>
            <div class="text-center">
                <span class="text-success text-lg font-bold tabular-nums">{{ $pipelineCompletionRate }}%</span>
                <p class="text-base-content/50 mt-0.5 text-[10px]">{{ __('dashboard.pipeline.completion_rate') }}</p>
            </div>
            <div class="text-center">
                <span class="{{ $bottleneckClass }} text-lg font-bold tabular-nums">{{ $pipelineBottleneck }}%</span>
                <p class="text-base-content/50 mt-0.5 text-[10px]">{{ __('dashboard.pipeline.bottleneck') }}</p>
            </div>
        </div>
    </div>

    {{-- 3-Column Metrics --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <div class="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-md">
                        <x-ts-icon class="size-3.5" name="user-plus" />
                    </div>
                    <span class="text-sm font-semibold">{{ __('dashboard.funnel.registration') }}</span>
                </div>
            </x-slot:header>
            @foreach ($registrationFunnel as $f)
                <div class="mt-3 first:mt-2">
                    <div class="mb-1 flex justify-between text-xs">
                        <span class="text-base-content/60">{{ $f['l'] }}</span>
                        <span class="font-semibold">{{ $f['v'] }} <span class="text-base-content/40 font-normal">({{ $f['p'] }}%)</span></span>
                    </div>
                    <div class="bg-base-200 h-2 overflow-hidden rounded-full">
                        <div class="{{ $f['c'] }} h-full rounded-full" style="width: {{ $f['p'] }}%"></div>
                    </div>
                </div>
            @endforeach
        </x-ts-card>

        <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <div class="bg-secondary/10 text-secondary flex size-6 items-center justify-center rounded-md">
                        <x-ts-icon class="size-3.5" name="clipboard-document-check" />
                    </div>
                    <span class="text-sm font-semibold">{{ __('dashboard.funnel.activity') }}</span>
                </div>
            </x-slot:header>
            <div class="mt-2">
                <div class="mb-1 flex justify-between text-xs">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.attendance') }}</span>
                    <span class="font-semibold">{{ $stats['attendanceVerified'] }}/{{ $activityMetrics['attD'] }} ({{ $activityMetrics['attP'] }}%)</span>
                </div>
                <div class="bg-base-200 h-2.5 overflow-hidden rounded-full">
                    <div
                        class="bg-success h-full rounded-full transition-all"
                        style="width: {{ $activityMetrics['attP'] }}%"
                    ></div>
                </div>
            </div>
            <div class="mt-3">
                <div class="mb-1 flex justify-between text-xs">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.logbook') }}</span>
                    <span class="font-semibold">{{ $stats['logbookVerified'] }}/{{ $activityMetrics['logD'] }} ({{ $activityMetrics['logP'] }}%)</span>
                </div>
                <div class="bg-base-200 h-2.5 overflow-hidden rounded-full">
                    <div
                        class="bg-secondary h-full rounded-full transition-all"
                        style="width: {{ $activityMetrics['logP'] }}%"
                    ></div>
                </div>
            </div>
            <div class="border-base-content/10 mt-3 flex items-center justify-between border-t pt-2 text-xs">
                <span class="text-base-content/60">{{ __('dashboard.funnel.pending') }}</span>
                <span class="text-warning font-semibold">{{ $stats['logbookPending'] }}</span>
            </div>
        </x-ts-card>

        <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
            <x-slot:header>
                <div class="flex items-center gap-2">
                    <div class="bg-primary/10 text-primary flex size-6 items-center justify-center rounded-md">
                        <x-ts-icon class="size-3.5" name="document-check" />
                    </div>
                    <span class="text-sm font-semibold">{{ __('dashboard.funnel.completion') }}</span>
                </div>
            </x-slot:header>
            <div class="mt-2">
                <div class="mb-1 flex justify-between text-xs">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.placement_fill') }}</span>
                    <span class="font-semibold">{{ $stats['placementFilled'] }}/{{ $stats['placementCapacity'] }} ({{ $completionMetrics['fillP'] }}%)</span>
                </div>
                <div class="bg-base-200 h-2.5 overflow-hidden rounded-full">
                    <div
                        class="bg-primary h-full rounded-full transition-all"
                        style="width: {{ $completionMetrics['fillP'] }}%"
                    ></div>
                </div>
            </div>
            <div class="mt-3">
                <div class="mb-1 flex justify-between text-xs">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.certificates') }}</span>
                    <span class="font-semibold">{{ $stats['certificatesIssued'] }}/{{ $completionMetrics['certTotal'] }} ({{ $completionMetrics['certP'] }}%)</span>
                </div>
                <div class="bg-base-200 h-2.5 overflow-hidden rounded-full">
                    <div
                        class="bg-success h-full rounded-full transition-all"
                        style="width: {{ $completionMetrics['certP'] }}%"
                    ></div>
                </div>
            </div>
            <div class="border-base-content/10 mt-3 space-y-1.5 border-t pt-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-base-content/60">{{ __('dashboard.stats.companies') }}</span
                    ><span class="font-semibold">{{ $stats['totalCompanies'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.companies_active') }}</span
                    ><span class="font-semibold">{{ $stats['companiesActive'] }}</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.partnerships') }}</span
                    ><span class="font-semibold">{{ $stats['totalPartnerships'] }}</span>
                </div>
            </div>
        </x-ts-card>
    </div>

    {{-- Bottom Row: Readiness --}}
    <x-ts-card shadowless class="bg-base-100 border-base-content/10 mb-6 border">
        <x-slot:header>
            <div class="flex items-center gap-2">
                <div class="bg-success/10 text-success flex size-6 items-center justify-center rounded-md">
                    <x-ts-icon class="size-3.5" name="check-circle" />
                </div>
                <span class="text-sm font-semibold">{{ __('dashboard.readiness.title') }}</span>
            </div>
        </x-slot:header>
        <div class="text-base-content/60 -mt-2 mb-3 text-sm">
            <span class="text-base-content/50 text-xs">{{ __('dashboard.readiness.subtitle') }}</span>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-5">
            @foreach ($readiness as $key => $status)
                <div class="bg-base-200/30 border-base-content/10 flex items-center gap-2.5 rounded-lg border px-3 py-3">
                    <x-ts-icon
                        class="size-4 shrink-0"
                        :name="$status['passed'] ? 'o-check-circle' : 'o-x-circle'"
                        :class="$status['passed'] ? 'text-success' : 'text-error'"
                    />
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium">{{ $status['label'] }}</p>
                        <p class="{{ $status['passed'] ? 'text-success' : 'text-error' }} text-[10px]">
                            {{ $status['status'] }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-ts-card>

    {{-- Super Admin System Cards --}}
    @hasrole('super_admin')
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-warning/10 text-warning flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon class="size-3.5" name="document-text" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.super_admin.audit_title') }}</span>
                    </div>
                </x-slot:header>
                <div class="mt-2 space-y-3">
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.total_audit_entries') }}</span>
                        <span class="font-semibold">{{ number_format($stats['totalAuditEntries'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.failed_logins_7d') }}</span>
                        <span class="{{ $failedLoginsClass }} font-semibold">{{ $stats['failedLogins7d'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.active_users_today') }}</span>
                        <span class="font-semibold">{{ $stats['activeUsersToday'] ?? 0 }}</span>
                    </div>
                </div>
            </x-ts-card>

            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-info/10 text-info flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon class="size-3.5" name="server" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.super_admin.system_title') }}</span>
                    </div>
                </x-slot:header>
                <div class="mt-2 space-y-3">
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.php_version') }}</span>
                        <span class="font-semibold">{{ PHP_VERSION }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.laravel_version') }}</span>
                        <span class="font-semibold">{{ app()->version() }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.environment') }}</span>
                        <span class="font-semibold uppercase">{{ app()->environment() }}</span>
                    </div>
                </div>
            </x-ts-card>

            <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
                <x-slot:header>
                    <div class="flex items-center gap-2">
                        <div class="bg-secondary/10 text-secondary flex size-6 items-center justify-center rounded-md">
                            <x-ts-icon class="size-3.5" name="document" />
                        </div>
                        <span class="text-sm font-semibold">{{ __('dashboard.super_admin.storage_title') }}</span>
                    </div>
                </x-slot:header>
                <div class="mt-2 space-y-3">
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.total_users') }}</span>
                        <span class="font-semibold">{{ number_format($totalUsersCombined) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.total_companies') }}</span>
                        <span class="font-semibold">{{ number_format($stats['totalCompanies']) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.internships') }}</span>
                        <span class="font-semibold">{{ number_format($stats['allInternships']) }}</span>
                    </div>
                </div>
            </x-ts-card>
        </div>
    @endhasrole

    {{-- Bottom Row: Activity & Quick Links --}}
    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
        <x-ts-card shadowless class="md:col-span-2" :header="__('dashboard.recent_activity')">
            @forelse ($this->getRecentActivities() as $activity)
                <div class="border-base-content/10 flex items-start gap-4 border-b py-3 last:border-0">
                    <div class="mt-1">
                        <x-ts-icon class="text-base-content/30 size-4" name="bolt" />
                    </div>
                    <div>
                        <div class="text-sm font-medium">
                            {{ __("activity.{$activity->description}") !== "activity.{$activity->description}" ? __("activity.{$activity->description}") : str($activity->description)->headline() }}
                        </div>
                        <div class="text-base-content/40 text-xs">
                            {{ $activity->created_at->locale(app()->getLocale())->diffForHumans() }}
                        </div>
                    </div>
                </div>
            @empty
                <x-core::widgets.empty-state icon="inbox" :title="__('dashboard.no_activity')" />
            @endforelse
        </x-ts-card>

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
                    @hasrole('super_admin')
                        <x-core::widgets.quick-link
                            :label="__('dashboard.system_settings')"
                            icon="cog-6-tooth"
                            link="{{ route('admin.settings') }}"
                        />
                    @endhasrole
                </div>
            </x-ts-card>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
