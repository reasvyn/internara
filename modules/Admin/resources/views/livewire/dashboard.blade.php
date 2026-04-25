<div>
    <x-ui::header 
        :title="__('admin::ui.dashboard.title')" 
        :subtitle="__('admin::ui.dashboard.subtitle')" 
        separator
        class="mb-6"
    />

    {{-- Unified Compact Grid: 2 (SM), 3 (MD), 4 (LG) columns --}}
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 items-start">
        
        {{-- Primary Stats --}}
        <x-ui::stat :title="__('admin::ui.dashboard.stats.total_interns')" :value="$summary['total_interns']" icon="tabler.users" variant="primary" class="shadow-sm border border-base-content/5" />
        <x-ui::stat :title="__('admin::ui.dashboard.stats.active_partners')" :value="$summary['active_partners']" icon="tabler.building" variant="secondary" class="shadow-sm border border-base-content/5" />
        <x-ui::stat :title="__('admin::ui.dashboard.stats.placement_rate')" :value="$summary['placement_rate'] . '%'" icon="tabler.chart-pie" variant="accent" class="shadow-sm border border-base-content/5" />
        
        @if($isSuperAdmin)
            <x-ui::stat :title="__('admin::ui.dashboard.users.active_sessions')" :value="$userDistribution['active_sessions']" icon="tabler.broadcast" variant="info" class="shadow-sm border border-base-content/5" />
            <x-ui::stat :title="__('admin::ui.dashboard.infrastructure.db_size')" :value="$infrastructure['db_size']" icon="tabler.database" variant="secondary" class="shadow-sm border border-base-content/5" />
            <x-ui::stat :title="__('admin::ui.dashboard.infrastructure.queue_pending')" :value="$infrastructure['queue_pending']" icon="tabler.list-details" :variant="$infrastructure['queue_pending'] > 0 ? 'warning' : 'metadata'" class="shadow-sm border border-base-content/5" />
        @endif

        {{-- Main Content: Recent Assessments (Wide Span) --}}
        <x-ui::card :title="__('admin::ui.dashboard.recent_assessments')" shadow separator class="lg:col-span-3 md:col-span-2 col-span-2 bg-base-100/50">
            <x-ui::table :rows="$this->registrations" :headers="[
                ['key' => 'student.name', 'label' => __('admin::ui.dashboard.table.student')],
                ['key' => 'placement.company_name', 'label' => __('admin::ui.dashboard.table.company')],
                ['key' => 'final_grade', 'label' => __('admin::ui.dashboard.table.final_grade')],
                ['key' => 'actions', 'label' => '']
            ]">
                @scope('cell_student.name', $reg)
                    <div class="flex items-center gap-2">
                        <x-ui::avatar :image="$reg->student->avatar_url" :title="$reg->student->name" size="w-7 h-7" />
                        <div class="flex flex-col">
                            <span class="font-bold text-xs truncate max-w-[100px] sm:max-w-none">{{ $reg->student->name }}</span>
                            <span class="text-[9px] opacity-40 uppercase tracking-tighter">{{ $reg->student->username }}</span>
                        </div>
                    </div>
                @endscope

                @scope('cell_final_grade', $reg)
                    @php $scoreCard = app(\Modules\Assessment\Services\Contracts\AssessmentService::class)->getScoreCard($reg->id); @endphp
                    @if($scoreCard['final_grade'])
                        <x-ui::badge :value="number_format($scoreCard['final_grade'], 1)" variant="primary" class="badge-sm font-bold" />
                    @else
                        <span class="text-[10px] opacity-30">{{ __('admin::ui.dashboard.table.not_graded') }}</span>
                    @endif
                @endscope

                @scope('actions', $reg)
                    <div class="flex gap-0.5">
                        <x-ui::button icon="tabler.certificate" variant="tertiary" class="btn-xs" link="{{ route('assessment.certificate', $reg->id) }}" />
                        <x-ui::button icon="tabler.file-description" variant="tertiary" class="btn-xs" link="{{ route('assessment.transcript', $reg->id) }}" />
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>

        {{-- System Health (Compact Span) --}}
        @if($isSuperAdmin)
            <x-ui::card :title="__('admin::ui.dashboard.system_status')" shadow separator class="lg:col-span-1 md:col-span-1 col-span-2 bg-base-100/50">
                <div class="grid grid-cols-2 gap-3 mb-4">
                    <div class="p-2.5 rounded-2xl bg-base-200/50 flex flex-col items-center text-center">
                        <span class="text-[9px] uppercase opacity-40 font-bold leading-none mb-1">{{ __('admin::ui.dashboard.security.failed_logins') }}</span>
                        <span @class(['text-lg font-black leading-none', 'text-error' => $securitySummary['failed_logins'] > 0])>{{ $securitySummary['failed_logins'] }}</span>
                    </div>
                    <div class="p-2.5 rounded-2xl bg-base-200/50 flex flex-col items-center text-center">
                        <span class="text-[9px] uppercase opacity-40 font-bold leading-none mb-1">{{ __('admin::ui.dashboard.infrastructure.queue_failed') }}</span>
                        <span @class(['text-lg font-black leading-none', 'text-error' => $infrastructure['queue_failed'] > 0])>{{ $infrastructure['queue_failed'] }}</span>
                    </div>
                </div>

                <div class="space-y-2 border-t border-base-content/5 pt-3">
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="opacity-60">{{ __('admin::ui.dashboard.security.throttled_attempts') }}</span>
                        <span class="font-bold">{{ $securitySummary['throttled_attempts'] }}</span>
                    </div>
                    <div class="flex justify-between items-center text-[11px]">
                        <span class="opacity-60 text-xs truncate max-w-[80px]">{{ __('admin::ui.dashboard.infrastructure.last_backup') }}</span>
                        <span class="font-medium opacity-80">{{ $infrastructure['last_backup'] ?? 'Never' }}</span>
                    </div>
                </div>
            </x-ui::card>
        @endif

        {{-- Activity Feed (Compact Span) --}}
        <div class="lg:col-span-1 md:col-span-1 col-span-2">
            <x-ui::slot-render name="admin.dashboard.side" />
        </div>

        {{-- At-Risk Students (Medium Span) --}}
        <x-ui::card :title="__('admin::ui.dashboard.at_risk_students')" shadow separator class="lg:col-span-2 md:col-span-2 col-span-2 bg-base-100/50">
            <x-ui::table :rows="$atRiskStudents" :headers="[
                ['key' => 'student_name', 'label' => __('admin::ui.dashboard.table.student')],
                ['key' => 'risk_level', 'label' => ''],
            ]">
                @scope('cell_student_name', $item)
                    <div class="flex flex-col">
                        <span class="text-xs font-medium">{{ $item['student_name'] }}</span>
                        <span class="text-[9px] opacity-50 truncate max-w-[150px]">{{ $item['reason'] }}</span>
                    </div>
                @endscope
                @scope('cell_risk_level', $item)
                    <x-ui::badge :value="$item['risk_level']" :variant="$item['risk_level'] === 'High' ? 'error' : 'warning'" class="badge-xs font-bold" />
                @endscope
            </x-ui::table>
        </x-ui::card>

        {{-- Quick Links (Compact Span) --}}
        <x-ui::card :title="__('admin::ui.dashboard.quick_links')" shadow separator class="lg:col-span-1 md:col-span-1 col-span-2 bg-base-100/50">
            <div class="flex flex-col gap-2">
                <x-ui::button :label="__('admin::ui.dashboard.user_management')" icon="tabler.users" variant="secondary" class="btn-sm text-[10px] uppercase w-full justify-start" link="{{ route('admin.students') }}" />
                <x-ui::button :label="__('admin::ui.dashboard.system_config')" icon="tabler.settings" variant="secondary" class="btn-sm text-[10px] uppercase w-full justify-start" link="{{ route('school.settings') }}" />
            </div>
        </x-ui::card>
    </div>
</div>
