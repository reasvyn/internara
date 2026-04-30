<div class="space-y-12">
    {{-- Page Header & Global Filters --}}
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <x-ui::header 
            :title="__('admin::ui.dashboard.title')" 
            :subtitle="__('admin::ui.dashboard.subtitle')" 
        />

        <div class="flex items-center gap-4 bg-base-200/50 p-2 rounded-2xl border border-base-content/5">
            <div class="flex items-center gap-2 px-3">
                <x-ui::icon name="tabler.calendar-stats" class="size-4 text-primary opacity-50" />
                <span class="text-[10px] font-black uppercase tracking-widest opacity-40">{{ __('internship::ui.academic_year') }}</span>
            </div>
            <select wire:model.live="filters.academic_year" class="select select-sm bg-base-100 border-none focus:ring-0 font-bold text-xs rounded-xl min-w-[140px]">
                @foreach($this->academicYears as $year)
                    <option value="{{ $year }}">{{ $year }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Executive Summary: Premium Enterprise Stats Grid --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6" wire:loading.class="opacity-50 transition-opacity">
        <x-ui::stat 
            :title="__('admin::ui.dashboard.stats.total_interns')" 
            :value="$summary['total_interns']" 
            icon="tabler.users" 
            variant="primary" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('admin::ui.dashboard.stats.active_partners')" 
            :value="$summary['active_partners']" 
            icon="tabler.building" 
            variant="info" 
            class="stat-enterprise" 
        />
        <x-ui::stat 
            :title="__('admin::ui.dashboard.stats.placement_rate')" 
            :value="$summary['placement_rate'] . '%'" 
            icon="tabler.chart-pie" 
            variant="success" 
            class="stat-enterprise" 
        />
        
        @if($isSuperAdmin)
            <x-ui::stat 
                :title="__('admin::ui.dashboard.users.active_sessions')" 
                :value="$userDistribution['active_sessions']" 
                icon="tabler.broadcast" 
                variant="metadata" 
                class="stat-enterprise" 
            />
        @endif
    </div>

    {{-- Operational Insight Section --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 items-start">
        
        {{-- Primary Content: Assessments & Risks (Wide Span) --}}
        <div class="lg:col-span-8 space-y-10" wire:loading.class="opacity-50 transition-opacity">
            {{-- Recent Assessments Table --}}
            <x-ui::card :title="__('admin::ui.dashboard.recent_assessments')" shadow class="card-enterprise">
                <x-ui::table :rows="$this->registrations" :headers="[
                    ['key' => 'student.name', 'label' => __('admin::ui.dashboard.table.student')],
                    ['key' => 'company_name', 'label' => __('admin::ui.dashboard.table.company')],
                    ['key' => 'final_grade', 'label' => __('admin::ui.dashboard.table.final_grade')],
                    ['key' => 'actions', 'label' => '']
                ]" class="table-enterprise">
                    @scope('cell_student.name', $row)
                        <div class="flex items-center gap-3">
                            <x-ui::avatar :image="$row['student']['avatar_url']" :title="$row['student']['name']" size="w-9 h-9" class="rounded-xl" />
                            <div class="flex flex-col">
                                <span class="font-bold text-sm text-base-content/90">{{ $row['student']['name'] }}</span>
                                <span class="text-[10px] font-black opacity-30 uppercase tracking-widest">{{ $row['student']['username'] }}</span>
                            </div>
                        </div>
                    @endscope

                    @scope('cell_company_name', $row)
                        <div class="flex items-center gap-2">
                            <x-ui::icon name="tabler.building" class="size-4 opacity-30" />
                            <span class="text-xs font-semibold opacity-70">{{ $row['company_name'] }}</span>
                        </div>
                    @endscope

                    @scope('cell_final_grade', $row)
                        @if($row['final_grade'])
                            <div class="flex items-center gap-3">
                                <div class="w-20 h-1.5 bg-base-200 rounded-full overflow-hidden hidden sm:block">
                                    <div class="h-full bg-primary transition-all duration-700" style="width: {{ $row['final_grade'] }}%"></div>
                                </div>
                                <span class="font-black text-xs text-primary">{{ number_format($row['final_grade'], 1) }}</span>
                            </div>
                        @else
                            <span class="text-[10px] opacity-20 italic font-medium">{{ __('admin::ui.dashboard.table.not_graded') }}</span>
                        @endif
                    @endscope

                    @scope('cell_actions', $row)
                        <div class="flex gap-1 justify-end">
                            <x-ui::button icon="tabler.certificate" variant="ghost" class="btn-xs text-primary/40 hover:text-primary hover:bg-primary/10" tooltip="Certificate" link="{{ route('assessment.certificate', $row['id']) }}" />
                            <x-ui::button icon="tabler.file-description" variant="ghost" class="btn-xs text-primary/40 hover:text-primary hover:bg-primary/10" tooltip="Transcript" link="{{ route('assessment.transcript', $row['id']) }}" />
                        </div>
                    @endscope
                </x-ui::table>
            </x-ui::card>

            {{-- At-Risk Monitoring --}}
            <x-ui::card :title="__('admin::ui.dashboard.at_risk_students')" shadow class="card-enterprise">
                <x-ui::table :rows="$atRiskStudents" :headers="[
                    ['key' => 'student_name', 'label' => __('admin::ui.dashboard.table.student')],
                    ['key' => 'risk_level', 'label' => ''],
                    ['key' => 'actions', 'label' => ''],
                ]" class="table-enterprise">
                    @scope('cell_student_name', $item)
                        <div class="flex flex-col">
                            <span class="text-xs font-bold text-base-content/80">{{ $item['student_name'] }}</span>
                            <span class="text-[9px] font-medium opacity-40 truncate max-w-[180px]">{{ $item['reason'] }}</span>
                        </div>
                    @endscope
                    @scope('cell_risk_level', $item)
                        <x-ui::badge 
                            :value="$item['risk_level']" 
                            :variant="$item['risk_level'] === 'High' ? 'error' : 'warning'" 
                            class="badge-sm font-black text-[9px] uppercase tracking-tighter" 
                        />
                    @endscope
                    @scope('cell_actions', $item)
                        <div class="flex justify-end">
                            <x-ui::button 
                                :label="__('journal::ui.view_journal')" 
                                variant="ghost" 
                                class="btn-xs text-[9px] font-black uppercase text-primary/60 hover:text-primary hover:bg-primary/10 rounded-lg"
                                link="{{ route('journal.index', ['registration' => $item['id']]) }}"
                            />
                        </div>
                    @endscope
                </x-ui::table>
            </x-ui::card>
        </div>

        {{-- Side Content: Activity & Health (Compact Span) --}}
        <div class="lg:col-span-4 space-y-10">
            {{-- Recent Activity Feed (Audit Trail) --}}
            <x-ui::card :title="__('admin::ui.dashboard.recent_activity')" shadow class="card-enterprise">
                <div class="space-y-6">
                    @forelse($recentActivities as $activity)
                        <div class="flex gap-4 group">
                            <div class="flex flex-col items-center gap-1">
                                <x-ui::avatar :image="$activity['causer_avatar']" :title="$activity['causer_name']" size="w-8 h-8" class="rounded-xl ring-2 ring-base-100 group-hover:ring-primary/20 transition-all" />
                                <div class="w-px h-full bg-base-content/5 group-last:hidden"></div>
                            </div>
                            <div class="flex flex-col pb-6 group-last:pb-0">
                                <p class="text-[11px] leading-relaxed text-base-content/70">
                                    <span class="font-bold text-base-content">{{ $activity['causer_name'] }}</span>
                                    {{ $activity['description'] }}
                                </p>
                                <span class="text-[9px] font-medium opacity-30 mt-1 uppercase tracking-wider">{{ $activity['created_at'] }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 flex flex-col items-center justify-center opacity-20 italic text-xs">
                            <x-ui::icon name="tabler.history" class="size-8 mb-2" />
                            <span>No recent activity logged</span>
                        </div>
                    @endforelse
                </div>
            </x-ui::card>

            {{-- System Health (Only for SuperAdmin) --}}
            @if($isSuperAdmin)
                <x-ui::card :title="__('admin::ui.dashboard.system_status')" shadow class="card-enterprise" wire:poll.30s>
                    <div class="grid grid-cols-1 gap-4 mb-8">
                        <div class="p-5 rounded-3xl bg-base-200/30 flex justify-between items-center border border-base-content/5 group hover:border-error/20 transition-all duration-300">
                            <div class="flex flex-col">
                                <span class="text-[9px] uppercase opacity-40 font-black mb-1 leading-none tracking-widest">{{ __('admin::ui.dashboard.security.failed_logins') }}</span>
                                <span @class(['text-2xl font-black leading-none tracking-tighter', 'text-error' => $securitySummary['failed_logins'] > 0, 'text-base-content/40' => $securitySummary['failed_logins'] == 0])>{{ $securitySummary['failed_logins'] }}</span>
                            </div>
                            <x-ui::icon name="tabler.shield-lock" @class(['size-10 opacity-10 transition-transform duration-500 group-hover:scale-110 group-hover:opacity-30', 'text-error' => $securitySummary['failed_logins'] > 0]) />
                        </div>
                        <div class="p-5 rounded-3xl bg-base-200/30 flex justify-between items-center border border-base-content/5 group hover:border-error/20 transition-all duration-300">
                            <div class="flex flex-col">
                                <span class="text-[9px] uppercase opacity-40 font-black mb-1 leading-none tracking-widest">{{ __('admin::ui.dashboard.infrastructure.queue_failed') }}</span>
                                <span @class(['text-2xl font-black leading-none tracking-tighter', 'text-error' => $infrastructure['queue_failed'] > 0, 'text-base-content/40' => $infrastructure['queue_failed'] == 0])>{{ $infrastructure['queue_failed'] }}</span>
                            </div>
                            <x-ui::icon name="tabler.activity-heartbeat" @class(['size-10 opacity-10 transition-transform duration-500 group-hover:scale-110 group-hover:opacity-30', 'text-error' => $infrastructure['queue_failed'] > 0]) />
                        </div>
                    </div>

                    <div class="space-y-4 border-t border-base-content/5 pt-6">
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold opacity-50 uppercase tracking-widest">{{ __('admin::ui.dashboard.security.throttled_attempts') }}</span>
                            <span class="font-black text-sm">{{ $securitySummary['throttled_attempts'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold opacity-50 uppercase tracking-widest">{{ __('admin::ui.dashboard.infrastructure.last_backup') }}</span>
                            <span class="font-black text-sm opacity-80">{{ $infrastructure['last_backup'] ?? 'Never' }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-[10px] font-bold opacity-50 uppercase tracking-widest">{{ __('admin::ui.dashboard.infrastructure.db_size') }}</span>
                            <span class="font-black text-sm opacity-80">{{ $infrastructure['db_size'] }}</span>
                        </div>
                    </div>
                </x-ui::card>
            @endif

            <div class="space-y-10">
                <x-ui::slot-render name="admin.dashboard.side" />
            </div>
        </div>
    </div>
</div>
