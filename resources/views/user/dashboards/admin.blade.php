<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.subtitle', ['name' => auth()->user()->name])" separator />

    {{-- People Overview --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3 mb-8">
        <x-shared::widgets.stat-card :title="__('dashboard.stats.total_students')" :value="$stats['totalStudents']" icon="o-users" color="text-primary" size="sm" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.instructors')" :value="$stats['totalTeachers']" icon="o-academic-cap" color="text-secondary" size="sm" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.supervisors')" :value="$stats['totalSupervisors']" icon="o-briefcase" color="text-accent" size="sm" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.departments')" :value="$stats['totalDepartments']" icon="o-building-library" color="text-primary" size="sm" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.companies')" :value="$stats['totalCompanies']" icon="o-building-office" color="text-secondary" size="sm" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.internships')" :value="$stats['activeInternships']" :suffix="__('dashboard.stats.active')" icon="o-flag" color="text-info" size="sm" />
    </div>

    {{-- PKL Lifecycle Pipeline --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <x-mary-icon name="o-rectangle-stack" class="size-5 text-primary" />
            <h3 class="font-semibold text-sm">{{ __('dashboard.pipeline.title') }}</h3>
        </div>

        @php
            $pipeline = [
                ['key' => 'registrationsPending', 'label' => __('dashboard.pipeline.pending'), 'value' => $stats['registrationsPending'], 'color' => 'bg-warning'],
                ['key' => 'registrationsActive', 'label' => __('dashboard.pipeline.active'), 'value' => $stats['registrationsActive'], 'color' => 'bg-info'],
                ['key' => 'placementFilled', 'label' => __('dashboard.pipeline.placement'), 'value' => $stats['placementFilled'], 'color' => 'bg-primary'],
                ['key' => 'logbookVerified', 'label' => __('dashboard.pipeline.logbook'), 'value' => $stats['logbookVerified'], 'color' => 'bg-secondary'],
                ['key' => 'certificatesIssued', 'label' => __('dashboard.pipeline.certificate'), 'value' => $stats['certificatesIssued'], 'color' => 'bg-success'],
            ];
            $maxPipeline = max(array_column($pipeline, 'value'), [1]);
            $maxPipeline = max($maxPipeline, 1);
        @endphp

        <div class="bg-base-100 border border-base-content/10 rounded-xl p-5">
            <div class="space-y-4">
                @foreach($pipeline as $item)
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium">{{ $item['label'] }}</span>
                            <span class="font-bold tabular-nums">{{ $item['value'] }}</span>
                        </div>
                        <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-700 {{ $item['color'] }}"
                                style="width: {{ max(1, ($item['value'] / $maxPipeline) * 100) }}%">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Lifecycle Metrics Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Registration Funnel --}}
        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-user-plus" class="size-4 text-primary" />
                    <span class="font-semibold text-sm">{{ __('dashboard.funnel.registration') }}</span>
                </div>
            </x-slot:title>
            <div class="space-y-3 mt-2">
                @php
                    $regTotal = max($stats['registrationsTotal'], 1);
                    $funnel = [
                        ['label' => __('dashboard.funnel.total'), 'value' => $stats['registrationsTotal'], 'pct' => 100, 'color' => 'bg-base-content/20'],
                        ['label' => __('dashboard.funnel.active'), 'value' => $stats['registrationsActive'], 'pct' => round(($stats['registrationsActive'] / $regTotal) * 100), 'color' => 'bg-info'],
                        ['label' => __('dashboard.funnel.completed'), 'value' => $stats['registrationsCompleted'], 'pct' => round(($stats['registrationsCompleted'] / $regTotal) * 100), 'color' => 'bg-success'],
                    ];
                @endphp
                @foreach($funnel as $f)
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-base-content/60">{{ $f['label'] }}</span>
                            <span class="font-semibold">{{ $f['value'] }} <span class="text-base-content/40 font-normal">({{ $f['pct'] }}%)</span></span>
                        </div>
                        <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full {{ $f['color'] }}" style="width: {{ $f['pct'] }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-mary-card>

        {{-- Attendance & Logbooks --}}
        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-clipboard-document-check" class="size-4 text-primary" />
                    <span class="font-semibold text-sm">{{ __('dashboard.funnel.activity') }}</span>
                </div>
            </x-slot:title>
            <div class="space-y-4 mt-2">
                @php
                    $attTotal = max($stats['attendanceVerified'] + $stats['attendanceUnverified'], 1);
                    $attPct = round(($stats['attendanceVerified'] / $attTotal) * 100);
                    $logTotal = max($stats['logbookVerified'] + $stats['logbookPending'], 1);
                    $logPct = round(($stats['logbookVerified'] / $logTotal) * 100);
                @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/60">{{ __('dashboard.funnel.attendance') }}</span>
                        <span class="font-semibold">{{ $stats['attendanceVerified'] }}/{{ $attTotal - 1 }} <span class="text-base-content/40 font-normal">({{ $attPct }}%)</span></span>
                    </div>
                    <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-success transition-all" style="width: {{ $attPct }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/60">{{ __('dashboard.funnel.logbook') }}</span>
                        <span class="font-semibold">{{ $stats['logbookVerified'] }}/{{ $logTotal - 1 }} <span class="text-base-content/40 font-normal">({{ $logPct }}%)</span></span>
                    </div>
                    <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-secondary transition-all" style="width: {{ $logPct }}%"></div>
                    </div>
                </div>
                <div class="pt-3 border-t border-base-content/10">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.funnel.pending') }}</span>
                        <span class="font-semibold text-warning">{{ $stats['logbookPending'] }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>

        {{-- Certificates & Placements --}}
        <x-mary-card class="bg-base-100 border border-base-content/10">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-mary-icon name="o-document-check" class="size-4 text-primary" />
                    <span class="font-semibold text-sm">{{ __('dashboard.funnel.completion') }}</span>
                </div>
            </x-slot:title>
            <div class="space-y-4 mt-2">
                @php
                    $capTotal = max($stats['placementCapacity'], 1);
                    $fillPct = round(($stats['placementFilled'] / $capTotal) * 100);
                    $certPct = $stats['certificatesTotal'] > 0 ? round(($stats['certificatesIssued'] / $stats['certificatesTotal']) * 100) : 0;
                @endphp
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/60">{{ __('dashboard.funnel.placement_fill') }}</span>
                        <span class="font-semibold">{{ $stats['placementFilled'] }}/{{ $capTotal - 1 }} <span class="text-base-content/40 font-normal">({{ $fillPct }}%)</span></span>
                    </div>
                    <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $fillPct }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="flex justify-between text-xs mb-1">
                        <span class="text-base-content/60">{{ __('dashboard.funnel.certificates') }}</span>
                        <span class="font-semibold">{{ $stats['certificatesIssued'] }}/{{ max($stats['certificatesTotal'], 1) }} <span class="text-base-content/40 font-normal">({{ $certPct }}%)</span></span>
                    </div>
                    <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full bg-success transition-all" style="width: {{ $certPct }}%"></div>
                    </div>
                </div>
                <div class="pt-3 border-t border-base-content/10 space-y-1.5">
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.funnel.companies_active') }}</span>
                        <span class="font-semibold">{{ $stats['companiesActive'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-base-content/60">{{ __('dashboard.funnel.partnerships') }}</span>
                        <span class="font-semibold">{{ $stats['totalPartnerships'] }}</span>
                    </div>
                </div>
            </div>
        </x-mary-card>
    </div>

    {{-- Bottom Row --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <x-mary-icon name="o-check-circle" class="size-4 text-primary" />
                        <span class="font-semibold text-sm">{{ __('dashboard.readiness.title') }}</span>
                    </div>
                </x-slot:title>
                <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('dashboard.readiness.subtitle') }}</span></x-slot:subtitle>
                <div class="space-y-3 mt-4">
                    @foreach($readiness as $key => $status)
                        <div class="flex items-center justify-between px-4 py-3 rounded-lg bg-base-200/30 border border-base-content/10">
                            <div class="flex items-center gap-3">
                                <x-mary-icon :name="$status['passed'] ? 'o-check-circle' : 'o-x-circle'" class="size-5 shrink-0" :class="$status['passed'] ? 'text-success' : 'text-error'" />
                                <span class="text-sm">{{ $status['label'] }}</span>
                            </div>
                            <x-mary-badge :label="$status['passed'] ? __('common.status.completed') : __('common.status.pending')" :class="$status['passed'] ? 'badge-success badge-sm' : 'badge-error badge-sm'" />
                        </div>
                    @endforeach
                </div>
            </x-mary-card>
        </div>

        <div class="flex flex-col gap-4">
            @include('user.dashboards._sidebar')

            <x-mary-card class="bg-gradient-to-br from-primary to-primary/80 text-white border-none">
                <div class="py-2">
                    <h4 class="font-semibold mb-1">{{ __('dashboard.help_title') }}</h4>
                    <p class="text-xs text-white/80 mb-4">{{ __('dashboard.help_desc', ['app' => config('app.name')]) }}</p>
                    <x-mary-button :label="__('dashboard.read_docs')" link="https://github.com/reasvyn/internara" class="btn-sm bg-white text-primary border-none hover:bg-white/90" />
                </div>
            </x-mary-card>
        </div>
    </div>

    @include('user.components.dashboard-guide')
</div>
