<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.subtitle', ['name' => auth()->user()->name])" separator />

    {{-- People Overview --}}
    <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-6">
        <x-core::widgets.stat-card :title="__('dashboard.stats.total_students')" :value="$stats['totalStudents']" icon="o-users" color="text-primary" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.instructors')" :value="$stats['totalTeachers']" icon="o-academic-cap" color="text-secondary" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.supervisors')" :value="$stats['totalSupervisors']" icon="o-briefcase" color="text-accent" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.departments')" :value="$stats['totalDepartments']" icon="o-building-library" color="text-primary" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.companies')" :value="$stats['totalCompanies']" icon="o-building-office" color="text-secondary" />
        <x-core::widgets.stat-card :title="__('dashboard.stats.internships')" :value="$stats['activeInternships']" :suffix="__('dashboard.stats.active')" icon="o-flag" color="text-info" />
    </div>

    {{-- PKL Funnel --}}
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-5 mb-6">
        <div class="flex items-start gap-3 mb-5">
            <div class="size-9 rounded-lg bg-primary/10 text-primary flex items-center justify-center shrink-0">
                <x-mary-icon name="o-funnel" class="size-4" />
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="font-semibold">{{ __('dashboard.pipeline.title') }}</h3>
                        <p class="text-xs text-base-content/50 mt-0.5">{{ __('dashboard.pipeline.subtitle') }}</p>
                    </div>
                    @php
                        $totalSt = $stats['totalStudents'];
                        $completed = $stats['certificatesIssued'];
                    @endphp
                    <div class="text-right shrink-0">
                        <span class="text-xs text-base-content/40 block">{{ __('dashboard.pipeline.throughput') }}</span>
                        <span class="text-lg font-bold tabular-nums {{ $totalSt > 0 ? 'text-success' : 'text-base-content/30' }}">
                            {{ $totalSt > 0 ? round(($completed / $totalSt) * 100) : 0 }}%
                        </span>
                    </div>
                </div>
            </div>
        </div>

        @php
            $registered = $stats['registrationsTotal'];
            $placed = $stats['placementFilled'];
            $active = $stats['registrationsActive'];
            $stages = [
                ['label' => __('dashboard.pipeline.students'), 'v' => $totalSt, 'c' => 'bg-base-content/20'],
                ['label' => __('dashboard.pipeline.registered'), 'v' => $registered, 'c' => 'bg-warning'],
                ['label' => __('dashboard.pipeline.placed'), 'v' => $placed, 'c' => 'bg-primary'],
                ['label' => __('dashboard.pipeline.active'), 'v' => $active, 'c' => 'bg-info'],
                ['label' => __('dashboard.pipeline.completed'), 'v' => $completed, 'c' => 'bg-success'],
            ];
            $maxV = max(array_column($stages, 'v')) ?: 1;
        @endphp

        <div class="space-y-0.5">
            @foreach($stages as $i => $stage)
                @php
                    $prevV = $i > 0 ? $stages[$i - 1]['v'] : $stage['v'];
                    $drop = $prevV > 0 ? round((1 - ($stage['v'] / $prevV)) * 100) : 0;
                @endphp
                <div class="flex items-center gap-3 py-2">
                    <div class="w-20 shrink-0 text-right">
                        <span class="text-xs font-medium text-base-content/70">{{ $stage['label'] }}</span>
                    </div>
                    <div class="flex-1 h-7 bg-base-200/50 rounded-md overflow-hidden relative">
                        <div class="h-full rounded-md transition-all duration-700 {{ $stage['c'] }}"
                            style="width: {{ max(2, ($stage['v'] / $maxV) * 100) }}%">
                        </div>
                        <span class="absolute inset-0 flex items-center px-2 text-xs font-bold tabular-nums {{ $stage['v'] > 0 ? 'text-white drop-shadow-sm' : 'text-base-content/40' }}">
                            {{ $stage['v'] }}
                        </span>
                    </div>
                    <div class="w-14 shrink-0 text-left text-xs {{ $i > 0 ? ($drop > 20 ? 'text-error font-medium' : 'text-base-content/40') : 'text-base-content/20' }}">
                        {{ $i > 0 ? "-{$drop}%" : '—' }}
                    </div>
                </div>
            @endforeach
        </div>

        @php
            $absorption = $totalSt > 0 ? round(($placed / $totalSt) * 100) : 0;
            $completionRate = $placed > 0 ? round(($completed / $placed) * 100) : 0;
            $bottleneck = $registered > $placed ? round((($registered - $placed) / $registered) * 100) : 0;
        @endphp
        <div class="grid grid-cols-3 gap-4 mt-4 pt-4 border-t border-base-content/10">
            <div class="text-center">
                <span class="text-lg font-bold tabular-nums text-primary">{{ $absorption }}%</span>
                <p class="text-[10px] text-base-content/50 mt-0.5">{{ __('dashboard.pipeline.absorption') }}</p>
            </div>
            <div class="text-center">
                <span class="text-lg font-bold tabular-nums text-success">{{ $completionRate }}%</span>
                <p class="text-[10px] text-base-content/50 mt-0.5">{{ __('dashboard.pipeline.completion_rate') }}</p>
            </div>
            <div class="text-center">
                <span class="text-lg font-bold tabular-nums {{ $bottleneck > 20 ? 'text-error' : 'text-base-content' }}">{{ $bottleneck }}%</span>
                <p class="text-[10px] text-base-content/50 mt-0.5">{{ __('dashboard.pipeline.bottleneck') }}</p>
            </div>
        </div>
    </div>

    {{-- 3-Column Metrics --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center"><x-mary-icon name="o-user-plus" class="size-3.5" /></div>
                    <span class="font-semibold text-sm">{{ __('dashboard.funnel.registration') }}</span>
                </div>
            </x-slot:title>
            @php $regT = max($stats['registrationsTotal'], 1); @endphp
            @foreach([
                ['l' => __('dashboard.funnel.total'), 'v' => $stats['registrationsTotal'], 'p' => 100, 'c' => 'bg-base-content/20'],
                ['l' => __('dashboard.funnel.active'), 'v' => $stats['registrationsActive'], 'p' => round(($stats['registrationsActive'] / $regT) * 100), 'c' => 'bg-info'],
                ['l' => __('dashboard.funnel.completed'), 'v' => $stats['registrationsCompleted'], 'p' => round(($stats['registrationsCompleted'] / $regT) * 100), 'c' => 'bg-success'],
            ] as $f)
                <div class="mt-3 first:mt-2">
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/60">{{ $f['l'] }}</span>
                        <span class="font-semibold">{{ $f['v'] }} <span class="text-base-content/40 font-normal">({{ $f['p'] }}%)</span></span>
                    </div>
                    <div class="h-2 bg-base-200 rounded-full overflow-hidden"><div class="h-full rounded-full {{ $f['c'] }}" style="width: {{ $f['p'] }}%"></div></div>
                </div>
            @endforeach
        </x-mary-card>

        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <div class="size-6 rounded-md bg-secondary/10 text-secondary flex items-center justify-center"><x-mary-icon name="o-clipboard-document-check" class="size-3.5" /></div>
                    <span class="font-semibold text-sm">{{ __('dashboard.funnel.activity') }}</span>
                </div>
            </x-slot:title>
            @php
                $attD = max($stats['attendanceVerified'] + $stats['attendanceUnverified'], 1);
                $attP = round(($stats['attendanceVerified'] / $attD) * 100);
                $logD = max($stats['logbookVerified'] + $stats['logbookPending'], 1);
                $logP = round(($stats['logbookVerified'] / $logD) * 100);
            @endphp
            <div class="mt-2">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.attendance') }}</span>
                    <span class="font-semibold">{{ $stats['attendanceVerified'] }}/{{ $attD }} ({{ $attP }}%)</span>
                </div>
                <div class="h-2.5 bg-base-200 rounded-full overflow-hidden"><div class="h-full rounded-full bg-success transition-all" style="width: {{ $attP }}%"></div></div>
            </div>
            <div class="mt-3">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.logbook') }}</span>
                    <span class="font-semibold">{{ $stats['logbookVerified'] }}/{{ $logD }} ({{ $logP }}%)</span>
                </div>
                <div class="h-2.5 bg-base-200 rounded-full overflow-hidden"><div class="h-full rounded-full bg-secondary transition-all" style="width: {{ $logP }}%"></div></div>
            </div>
            <div class="mt-3 pt-2 border-t border-base-content/10 flex items-center justify-between text-xs">
                <span class="text-base-content/60">{{ __('dashboard.funnel.pending') }}</span>
                <span class="font-semibold text-warning">{{ $stats['logbookPending'] }}</span>
            </div>
        </x-mary-card>

        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center"><x-mary-icon name="o-document-check" class="size-3.5" /></div>
                    <span class="font-semibold text-sm">{{ __('dashboard.funnel.completion') }}</span>
                </div>
            </x-slot:title>
            @php
                $capD = max($stats['placementCapacity'], 1);
                $fillP = round(($stats['placementFilled'] / $capD) * 100);
                $certP = $stats['certificatesTotal'] > 0 ? round(($stats['certificatesIssued'] / $stats['certificatesTotal']) * 100) : 0;
            @endphp
            <div class="mt-2">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.placement_fill') }}</span>
                    <span class="font-semibold">{{ $stats['placementFilled'] }}/{{ $stats['placementCapacity'] }} ({{ $fillP }}%)</span>
                </div>
                <div class="h-2.5 bg-base-200 rounded-full overflow-hidden"><div class="h-full rounded-full bg-primary transition-all" style="width: {{ $fillP }}%"></div></div>
            </div>
            <div class="mt-3">
                <div class="flex justify-between text-xs mb-1">
                    <span class="text-base-content/60">{{ __('dashboard.funnel.certificates') }}</span>
                    <span class="font-semibold">{{ $stats['certificatesIssued'] }}/{{ max($stats['certificatesTotal'], 1) }} ({{ $certP }}%)</span>
                </div>
                <div class="h-2.5 bg-base-200 rounded-full overflow-hidden"><div class="h-full rounded-full bg-success transition-all" style="width: {{ $certP }}%"></div></div>
            </div>
            <div class="mt-3 pt-2 border-t border-base-content/10 space-y-1.5">
                <div class="flex items-center justify-between text-xs"><span class="text-base-content/60">{{ __('dashboard.stats.companies') }}</span><span class="font-semibold">{{ $stats['totalCompanies'] }}</span></div>
                <div class="flex items-center justify-between text-xs"><span class="text-base-content/60">{{ __('dashboard.funnel.companies_active') }}</span><span class="font-semibold">{{ $stats['companiesActive'] }}</span></div>
                <div class="flex items-center justify-between text-xs"><span class="text-base-content/60">{{ __('dashboard.funnel.partnerships') }}</span><span class="font-semibold">{{ $stats['totalPartnerships'] }}</span></div>
            </div>
        </x-mary-card>
    </div>

    {{-- Bottom Row: Readiness --}}
    <x-mary-card class="bg-base-100 border border-base-content/10 mb-6">
        <x-slot:title>
            <div class="flex items-center gap-2">
                <div class="size-6 rounded-md bg-success/10 text-success flex items-center justify-center"><x-mary-icon name="o-check-circle" class="size-3.5" /></div>
                <span class="font-semibold text-sm">{{ __('dashboard.readiness.title') }}</span>
            </div>
        </x-slot:title>
        <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('dashboard.readiness.subtitle') }}</span></x-slot:subtitle>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 mt-4">
            @foreach($readiness as $key => $status)
                <div class="flex items-center gap-2.5 px-3 py-3 rounded-lg bg-base-200/30 border border-base-content/10">
                    <x-mary-icon :name="$status['passed'] ? 'o-check-circle' : 'o-x-circle'" class="size-4 shrink-0" :class="$status['passed'] ? 'text-success' : 'text-error'" />
                    <div class="min-w-0">
                        <p class="text-xs font-medium truncate">{{ $status['label'] }}</p>
                        <p class="text-[10px] {{ $status['passed'] ? 'text-success' : 'text-error' }}">{{ $status['status'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </x-mary-card>

    {{-- Super Admin System Cards --}}
    @php $isSuperAdmin = auth()->user()?->hasRole('super_admin'); @endphp
    @if($isSuperAdmin)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="size-6 rounded-md bg-warning/10 text-warning flex items-center justify-center"><x-mary-icon name="o-document-text" class="size-3.5" /></div>
                        <span class="font-semibold text-sm">{{ __('dashboard.super_admin.audit_title') }}</span>
                    </div>
                </x-slot:title>
                <div class="space-y-3 mt-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.total_audit_entries') }}</span>
                        <span class="font-semibold">{{ number_format($stats['totalAuditEntries'] ?? 0) }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.failed_logins_7d') }}</span>
                        <span class="font-semibold {{ ($stats['failedLogins7d'] ?? 0) > 0 ? 'text-error' : '' }}">{{ $stats['failedLogins7d'] ?? 0 }}</span>
                    </div>
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.active_users_today') }}</span>
                        <span class="font-semibold">{{ $stats['activeUsersToday'] ?? 0 }}</span>
                    </div>
                </div>
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="size-6 rounded-md bg-info/10 text-info flex items-center justify-center"><x-mary-icon name="o-server" class="size-3.5" /></div>
                        <span class="font-semibold text-sm">{{ __('dashboard.super_admin.system_title') }}</span>
                    </div>
                </x-slot:title>
                <div class="space-y-3 mt-2">
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
            </x-mary-card>

            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="size-6 rounded-md bg-secondary/10 text-secondary flex items-center justify-center"><x-mary-icon name="o-database" class="size-3.5" /></div>
                        <span class="font-semibold text-sm">{{ __('dashboard.super_admin.storage_title') }}</span>
                    </div>
                </x-slot:title>
                <div class="space-y-3 mt-2">
                    <div class="flex justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.super_admin.total_users') }}</span>
                        <span class="font-semibold">{{ number_format($stats['totalStudents'] + $stats['totalTeachers'] + $stats['totalSupervisors']) }}</span>
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
            </x-mary-card>
        </div>
    @endif

    {{-- Bottom Row: Activity & Quick Links --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <x-mary-card :title="__('dashboard.recent_activity')" separator class="md:col-span-2">
            @forelse($this->getRecentActivities() as $activity)
                <div class="flex items-start gap-4 py-3 border-b last:border-0 border-base-content/10">
                    <div class="mt-1">
                        <x-mary-icon name="o-bolt" class="size-4 text-base-content/30" />
                    </div>
                    <div>
                        <div class="text-sm font-medium">{{ __("activity.{$activity->description}") !== "activity.{$activity->description}" ? __("activity.{$activity->description}") : str($activity->description)->headline() }}</div>
                        <div class="text-xs text-base-content/40">{{ $activity->created_at->locale(app()->getLocale())->diffForHumans() }}</div>
                    </div>
                </div>
            @empty
                <x-core::widgets.empty-state icon="o-inbox" :title="__('dashboard.no_activity')" />
            @endforelse
        </x-mary-card>

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
