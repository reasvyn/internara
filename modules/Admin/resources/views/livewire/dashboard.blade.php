<div class="space-y-8">
    {{-- Page Header --}}
    <x-ui::header 
        :title="__('admin::ui.dashboard.title')" 
        :subtitle="__('admin::ui.dashboard.subtitle')" 
        separator
    />

    {{-- Executive Summary: 2-3-4 Grid System --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        <x-ui::stat 
            :title="__('admin::ui.dashboard.stats.total_interns')" 
            :value="$summary['total_interns']" 
            icon="tabler.users" 
            variant="primary" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('admin::ui.dashboard.stats.active_partners')" 
            :value="$summary['active_partners']" 
            icon="tabler.building" 
            variant="secondary" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        <x-ui::stat 
            :title="__('admin::ui.dashboard.stats.placement_rate')" 
            :value="$summary['placement_rate'] . '%'" 
            icon="tabler.chart-pie" 
            variant="accent" 
            class="shadow-sm border border-base-content/5 bg-base-100/50" 
        />
        
        @if($isSuperAdmin)
            <x-ui::stat 
                :title="__('admin::ui.dashboard.users.active_sessions')" 
                :value="$userDistribution['active_sessions']" 
                icon="tabler.broadcast" 
                variant="info" 
                class="shadow-sm border border-base-content/5 bg-base-100/50" 
            />
        @endif
    </div>

    {{-- Operational Insight Section --}}
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-8 items-start">
        
        {{-- Primary Content: Assessments & Risks (Wide Span) --}}
        <div class="md:col-span-2 lg:col-span-3 space-y-8">
            {{-- Recent Assessments Table --}}
            <x-ui::card :title="__('admin::ui.dashboard.recent_assessments')" shadow separator class="bg-base-100/50 border-none">
                <x-ui::table :rows="$this->registrations" :headers="[
                    ['key' => 'student.name', 'label' => __('admin::ui.dashboard.table.student')],
                    ['key' => 'placement.company_name', 'label' => __('admin::ui.dashboard.table.company')],
                    ['key' => 'final_grade', 'label' => __('admin::ui.dashboard.table.final_grade')],
                    ['key' => 'actions', 'label' => '']
                ]">
                    @scope('cell_student.name', $reg)
                        <div class="flex items-center gap-3">
                            <x-ui::avatar :image="$reg->student->avatar_url" :title="$reg->student->name" size="w-8 h-8" />
                            <div class="flex flex-col">
                                <span class="font-bold text-xs truncate max-w-[150px]">{{ $reg->student->name }}</span>
                                <span class="text-[10px] opacity-40 uppercase tracking-wider">{{ $reg->student->username }}</span>
                            </div>
                        </div>
                    @endscope

                    @scope('cell_final_grade', $reg)
                        @php $scoreCard = app(\Modules\Assessment\Services\Contracts\AssessmentService::class)->getScoreCard($reg->id); @endphp
                        @if($scoreCard['final_grade'])
                            <div class="flex items-center gap-2">
                                <div class="w-16 h-1.5 bg-base-300 rounded-full overflow-hidden hidden sm:block">
                                    <div class="h-full bg-primary" style="width: {{ $scoreCard['final_grade'] }}%"></div>
                                </div>
                                <span class="font-black text-xs text-primary">{{ number_format($scoreCard['final_grade'], 1) }}</span>
                            </div>
                        @else
                            <span class="text-[10px] opacity-30 italic">{{ __('admin::ui.dashboard.table.not_graded') }}</span>
                        @endif
                    @endscope

                    @scope('actions', $reg)
                        <div class="flex gap-1 justify-end">
                            <x-ui::button icon="tabler.certificate" variant="tertiary" class="btn-xs hover:btn-primary" tooltip="Certificate" link="{{ route('assessment.certificate', $reg->id) }}" />
                            <x-ui::button icon="tabler.file-description" variant="tertiary" class="btn-xs hover:btn-primary" tooltip="Transcript" link="{{ route('assessment.transcript', $reg->id) }}" />
                        </div>
                    @endscope
                </x-ui::table>
            </x-ui::card>

            {{-- At-Risk Monitoring --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <x-ui::card :title="__('admin::ui.dashboard.at_risk_students')" shadow separator class="bg-base-100/50">
                    <x-ui::table :rows="$atRiskStudents" :headers="[
                        ['key' => 'student_name', 'label' => __('admin::ui.dashboard.table.student')],
                        ['key' => 'risk_level', 'label' => ''],
                    ]">
                        @scope('cell_student_name', $item)
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-base-content/80">{{ $item['student_name'] }}</span>
                                <span class="text-[10px] opacity-50 truncate max-w-[200px]">{{ $item['reason'] }}</span>
                            </div>
                        @endscope
                        @scope('cell_risk_level', $item)
                            <x-ui::badge :value="$item['risk_level']" :variant="$item['risk_level'] === 'High' ? 'error' : 'warning'" class="badge-sm font-black text-[9px] uppercase" />
                        @endscope
                    </x-ui::table>
                </x-ui::card>

                <x-ui::card :title="__('admin::ui.dashboard.quick_links')" shadow separator class="bg-base-100/50">
                    <div class="grid grid-cols-1 gap-3">
                        <x-ui::button :label="__('admin::ui.dashboard.user_management')" icon="tabler.users" variant="secondary" class="btn-md text-[11px] font-bold uppercase w-full justify-start shadow-sm" link="{{ route('admin.students') }}" />
                        <x-ui::button :label="__('admin::ui.dashboard.system_config')" icon="tabler.settings" variant="secondary" class="btn-md text-[11px] font-bold uppercase w-full justify-start shadow-sm" link="{{ route('school.settings') }}" />
                    </div>
                </x-ui::card>
            </div>
        </div>

        {{-- Side Content: Activity & Health (Compact Span) --}}
        <div class="space-y-8">
            {{-- System Health (Only for SuperAdmin) --}}
            @if($isSuperAdmin)
                <x-ui::card :title="__('admin::ui.dashboard.system_status')" shadow separator class="bg-base-100/50">
                    <div class="grid grid-cols-1 gap-4 mb-6">
                        <div class="p-4 rounded-2xl bg-base-200/50 flex justify-between items-center border border-base-content/5">
                            <div class="flex flex-col">
                                <span class="text-[10px] uppercase opacity-40 font-black mb-1 leading-none tracking-widest">{{ __('admin::ui.dashboard.security.failed_logins') }}</span>
                                <span @class(['text-2xl font-black leading-none', 'text-error' => $securitySummary['failed_logins'] > 0, 'text-base-content/40' => $securitySummary['failed_logins'] == 0])>{{ $securitySummary['failed_logins'] }}</span>
                            </div>
                            <x-ui::icon name="tabler.shield-lock" @class(['w-8 h-8 opacity-20', 'text-error' => $securitySummary['failed_logins'] > 0]) />
                        </div>
                        <div class="p-4 rounded-2xl bg-base-200/50 flex justify-between items-center border border-base-content/5">
                            <div class="flex flex-col">
                                <span class="text-[10px] uppercase opacity-40 font-black mb-1 leading-none tracking-widest">{{ __('admin::ui.dashboard.infrastructure.queue_failed') }}</span>
                                <span @class(['text-2xl font-black leading-none', 'text-error' => $infrastructure['queue_failed'] > 0, 'text-base-content/40' => $infrastructure['queue_failed'] == 0])>{{ $infrastructure['queue_failed'] }}</span>
                            </div>
                            <x-ui::icon name="tabler.activity-heartbeat" @class(['w-8 h-8 opacity-20', 'text-error' => $infrastructure['queue_failed'] > 0]) />
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-base-content/5 pt-4">
                        <div class="flex justify-between items-center text-xs">
                            <span class="opacity-60">{{ __('admin::ui.dashboard.security.throttled_attempts') }}</span>
                            <span class="font-black">{{ $securitySummary['throttled_attempts'] }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="opacity-60">{{ __('admin::ui.dashboard.infrastructure.last_backup') }}</span>
                            <span class="font-medium opacity-80">{{ $infrastructure['last_backup'] ?? 'Never' }}</span>
                        </div>
                    </div>
                </x-ui::card>
            @endif

            {{-- Injected Activity Feed & Widgets --}}
            <div class="space-y-8">
                <x-ui::slot-render name="admin.dashboard.side" />
            </div>
        </div>
    </div>
</div>
