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

    {{-- PKL Pipeline --}}
    <div class="bg-base-100 border border-base-content/10 rounded-xl p-5 mb-8">
        <div class="flex items-center gap-2 mb-5">
            <div class="size-8 rounded-lg bg-primary/10 text-primary flex items-center justify-center">
                <x-mary-icon name="o-arrow-trending-up" class="size-4" />
            </div>
            <div>
                <h3 class="font-semibold text-sm">{{ __('dashboard.pipeline.title') }}</h3>
                <p class="text-xs text-base-content/50">{{ __('dashboard.pipeline.subtitle') }}</p>
            </div>
        </div>

        @php
            $pipeline = [
                ['key' => 'registrationsPending', 'label' => __('dashboard.pipeline.pending'), 'value' => $stats['registrationsPending'], 'color' => 'bg-warning'],
                ['key' => 'registrationsActive', 'label' => __('dashboard.pipeline.active'), 'value' => $stats['registrationsActive'], 'color' => 'bg-info'],
                ['key' => 'placementFilled', 'label' => __('dashboard.pipeline.placement'), 'value' => $stats['placementFilled'], 'color' => 'bg-primary'],
                ['key' => 'logbookVerified', 'label' => __('dashboard.pipeline.logbook'), 'value' => $stats['logbookVerified'], 'color' => 'bg-secondary'],
                ['key' => 'certificatesIssued', 'label' => __('dashboard.pipeline.certificate'), 'value' => $stats['certificatesIssued'], 'color' => 'bg-success'],
            ];
            $maxV = max(array_column($pipeline, 'value')) ?: 1;
        @endphp

        <div class="grid grid-cols-2 sm:grid-cols-5 gap-4">
            @foreach($pipeline as $i => $item)
                <div class="text-center">
                    <div class="relative h-32 bg-base-200/50 rounded-lg flex flex-col items-center justify-end px-2 pb-3 overflow-hidden">
                        <div class="absolute bottom-0 left-0 right-0 rounded-lg transition-all duration-700 {{ $item['color'] }}"
                            style="height: {{ max(3, ($item['value'] / $maxV) * 100) }}%">
                        </div>
                        <div class="relative z-10 text-center">
                            <span class="block text-xl font-bold tabular-nums text-white drop-shadow-sm">{{ $item['value'] }}</span>
                            <span class="block text-[10px] text-white/80 mt-0.5">{{ $item['label'] }}</span>
                        </div>
                    </div>
                    @if($i < count($pipeline) - 1)
                        <div class="text-center mt-1">
                            <x-mary-icon name="o-arrow-down" class="size-3 text-base-content/20" />
                        </div>
                    @endif
                </div>
            @endforeach
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
