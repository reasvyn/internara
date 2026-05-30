<div>
    <x-mary-header :title="__('dashboard.title')" :subtitle="__('dashboard.subtitle', ['name' => auth()->user()->name])" separator />

    {{-- People Overview --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
        <x-shared::widgets.stat-card :title="__('dashboard.stats.total_students')" :value="$stats['totalStudents']" icon="o-users" color="text-primary" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.instructors')" :value="$stats['totalTeachers']" icon="o-academic-cap" color="text-secondary" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.supervisors')" :value="$stats['totalSupervisors']" icon="o-briefcase" color="text-accent" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.departments')" :value="$stats['totalDepartments']" icon="o-building-library" color="text-primary" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.companies')" :value="$stats['totalCompanies']" icon="o-building-office" color="text-secondary" />
        <x-shared::widgets.stat-card :title="__('dashboard.stats.internships')" :value="$stats['activeInternships']" :suffix="__('dashboard.stats.active')" icon="o-flag" color="text-info" />
    </div>

    {{-- PKL Funnel --}}
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-5 mb-8">
        <div class="flex items-start gap-3 mb-6">
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
                        $registered = $stats['registrationsTotal'];
                        $placed = $stats['placementFilled'];
                        $active = $stats['registrationsActive'];
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
            $stages = [
                ['key' => 'totalStudents', 'label' => __('dashboard.pipeline.students'), 'v' => $totalSt, 'c' => 'bg-base-content/20'],
                ['key' => 'registrationsTotal', 'label' => __('dashboard.pipeline.registered'), 'v' => $registered, 'c' => 'bg-warning'],
                ['key' => 'placementFilled', 'label' => __('dashboard.pipeline.placed'), 'v' => $placed, 'c' => 'bg-primary'],
                ['key' => 'registrationsActive', 'label' => __('dashboard.pipeline.active'), 'v' => $active, 'c' => 'bg-info'],
                ['key' => 'certificatesIssued', 'label' => __('dashboard.pipeline.completed'), 'v' => $completed, 'c' => 'bg-success'],
            ];
            $maxV = max(array_column($stages, 'v')) ?: 1;
        @endphp

        <div class="space-y-1">
            @foreach($stages as $i => $stage)
                @php
                    $prevV = $i > 0 ? $stages[$i - 1]['v'] : $stage['v'];
                    $drop = $prevV > 0 ? round((1 - ($stage['v'] / $prevV)) * 100) : 0;
                @endphp
                <div class="flex items-center gap-3 py-2">
                    <div class="w-24 shrink-0 text-right">
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
                    <div class="w-16 shrink-0 text-left">
                        @if($i > 0)
                            <span class="text-xs {{ $drop > 20 ? 'text-error font-medium' : 'text-base-content/40' }}">
                                -{{ $drop }}%
                            </span>
                        @else
                            <span class="text-xs text-base-content/20">—</span>
                        @endif
                    </div>
                </div>
                @if($i < count($stages) - 1)
                    <div class="flex items-center gap-3">
                        <div class="w-24 shrink-0"></div>
                        <div class="flex-1 flex items-center gap-1.5 px-0.5">
                            <div class="h-px flex-1 bg-base-content/10"></div>
                            <x-mary-icon name="o-arrow-down" class="size-2.5 text-base-content/20" />
                        </div>
                        <div class="w-16 shrink-0"></div>
                    </div>
                @endif
            @endforeach
        </div>

        @php
            $absorption = $totalSt > 0 ? round(($placed / $totalSt) * 100) : 0;
            $completionRate = $placed > 0 ? round(($completed / $placed) * 100) : 0;
            $bottleneck = $registered > $placed ? round((($registered - $placed) / $registered) * 100) : 0;
        @endphp
        <div class="grid grid-cols-3 gap-4 mt-5 pt-4 border-t border-base-content/10">
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

    {{-- Main Content: 2/3 + 1/3 --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        {{-- Left: Lifecycle Cards --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center">
                                <x-mary-icon name="o-user-plus" class="size-3.5" />
                            </div>
                            <span class="font-semibold text-sm">{{ __('dashboard.funnel.registration') }}</span>
                        </div>
                    </x-slot:title>
                    @php
                        $regT = max($stats['registrationsTotal'], 1);
                        $funnel = [
                            ['label' => __('dashboard.funnel.total'), 'v' => $stats['registrationsTotal'], 'p' => 100, 'c' => 'bg-base-content/20'],
                            ['label' => __('dashboard.funnel.active'), 'v' => $stats['registrationsActive'], 'p' => round(($stats['registrationsActive'] / $regT) * 100), 'c' => 'bg-info'],
                            ['label' => __('dashboard.funnel.completed'), 'v' => $stats['registrationsCompleted'], 'p' => round(($stats['registrationsCompleted'] / $regT) * 100), 'c' => 'bg-success'],
                        ];
                    @endphp
                    <div class="space-y-3 mt-2">
                        @foreach($funnel as $f)
                            <div>
                                <div class="flex justify-between text-xs mb-1">
                                    <span class="text-base-content/60">{{ $f['label'] }}</span>
                                    <span class="font-semibold">{{ $f['v'] }} <span class="text-base-content/40 font-normal">({{ $f['p'] }}%)</span></span>
                                </div>
                                <div class="h-2 bg-base-200 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $f['c'] }}" style="width: {{ $f['p'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-mary-card>

                <x-mary-card class="bg-base-100 border border-base-content/10">
                    <x-slot:title>
                        <div class="flex items-center gap-2">
                            <div class="size-6 rounded-md bg-secondary/10 text-secondary flex items-center justify-center">
                                <x-mary-icon name="o-clipboard-document-check" class="size-3.5" />
                            </div>
                            <span class="font-semibold text-sm">{{ __('dashboard.funnel.activity') }}</span>
                        </div>
                    </x-slot:title>
                    @php
                        $attD = max($stats['attendanceVerified'] + $stats['attendanceUnverified'], 1);
                        $attP = round(($stats['attendanceVerified'] / $attD) * 100);
                        $logD = max($stats['logbookVerified'] + $stats['logbookPending'], 1);
                        $logP = round(($stats['logbookVerified'] / $logD) * 100);
                    @endphp
                    <div class="space-y-4 mt-2">
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-base-content/60">{{ __('dashboard.funnel.attendance') }}</span>
                                <span class="font-semibold">{{ $stats['attendanceVerified'] }}/{{ $attD }} ({{ $attP }}%)</span>
                            </div>
                            <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-success transition-all" style="width: {{ $attP }}%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between text-xs mb-1">
                                <span class="text-base-content/60">{{ __('dashboard.funnel.logbook') }}</span>
                                <span class="font-semibold">{{ $stats['logbookVerified'] }}/{{ $logD }} ({{ $logP }}%)</span>
                            </div>
                            <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                                <div class="h-full rounded-full bg-secondary transition-all" style="width: {{ $logP }}%"></div>
                            </div>
                        </div>
                        <div class="pt-2 border-t border-base-content/10 flex items-center justify-between text-xs">
                            <span class="text-base-content/60">{{ __('dashboard.funnel.pending') }}</span>
                            <span class="font-semibold text-warning">{{ $stats['logbookPending'] }}</span>
                        </div>
                    </div>
                </x-mary-card>
            </div>

            {{-- Readiness --}}
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="size-6 rounded-md bg-success/10 text-success flex items-center justify-center">
                            <x-mary-icon name="o-check-circle" class="size-3.5" />
                        </div>
                        <span class="font-semibold text-sm">{{ __('dashboard.readiness.title') }}</span>
                    </div>
                </x-slot:title>
                <x-slot:subtitle><span class="text-xs text-base-content/50">{{ __('dashboard.readiness.subtitle') }}</span></x-slot:subtitle>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mt-4">
                    @foreach($readiness as $key => $status)
                        <div class="flex items-center gap-2.5 px-3 py-2.5 rounded-lg bg-base-200/30 border border-base-content/10">
                            <x-mary-icon :name="$status['passed'] ? 'o-check-circle' : 'o-x-circle'" class="size-4 shrink-0" :class="$status['passed'] ? 'text-success' : 'text-error'" />
                            <div class="min-w-0">
                                <p class="text-xs font-medium truncate">{{ $status['label'] }}</p>
                                <p class="text-[10px] {{ $status['passed'] ? 'text-success' : 'text-error' }}">{{ $status['passed'] ? __('common.status.completed') : __('common.status.pending') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-mary-card>
        </div>

        {{-- Right: Sidebar --}}
        <div class="space-y-6">
            <x-mary-card class="bg-base-100 border border-base-content/10">
                <x-slot:title>
                    <div class="flex items-center gap-2">
                        <div class="size-6 rounded-md bg-primary/10 text-primary flex items-center justify-center">
                            <x-mary-icon name="o-document-check" class="size-3.5" />
                        </div>
                        <span class="font-semibold text-sm">{{ __('dashboard.funnel.completion') }}</span>
                    </div>
                </x-slot:title>
                @php
                    $capD = max($stats['placementCapacity'], 1);
                    $fillP = round(($stats['placementFilled'] / $capD) * 100);
                    $certP = $stats['certificatesTotal'] > 0 ? round(($stats['certificatesIssued'] / $stats['certificatesTotal']) * 100) : 0;
                @endphp
                <div class="space-y-4 mt-2">
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-base-content/60">{{ __('dashboard.funnel.placement_fill') }}</span>
                            <span class="font-semibold">{{ $stats['placementFilled'] }}/{{ $stats['placementCapacity'] }} ({{ $fillP }}%)</span>
                        </div>
                        <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $fillP }}%"></div>
                        </div>
                    </div>
                    <div>
                        <div class="flex justify-between text-xs mb-1">
                            <span class="text-base-content/60">{{ __('dashboard.funnel.certificates') }}</span>
                            <span class="font-semibold">{{ $stats['certificatesIssued'] }}/{{ max($stats['certificatesTotal'], 1) }} ({{ $certP }}%)</span>
                        </div>
                        <div class="h-2.5 bg-base-200 rounded-full overflow-hidden">
                            <div class="h-full rounded-full bg-success transition-all" style="width: {{ $certP }}%"></div>
                        </div>
                    </div>
                    <div class="pt-3 border-t border-base-content/10 space-y-2">
                        <div class="flex items-center justify-between text-xs">
                            <span class="text-base-content/60">{{ __('dashboard.stats.companies') }}</span>
                            <span class="font-semibold">{{ $stats['totalCompanies'] }}</span>
                        </div>
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
