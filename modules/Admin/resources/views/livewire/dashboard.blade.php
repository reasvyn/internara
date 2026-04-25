<div>
    <x-ui::header 
        :title="__('admin::ui.dashboard.title')" 
        :subtitle="__('admin::ui.dashboard.subtitle')" 
        separator
    />

    {{-- Main Stats Row --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6 mb-6">
        <x-ui::stat :title="__('admin::ui.dashboard.stats.total_interns')" :value="$summary['total_interns']" icon="tabler.users" variant="primary" class="shadow-sm border border-base-content/5" />
        <x-ui::stat :title="__('admin::ui.dashboard.stats.active_partners')" :value="$summary['active_partners']" icon="tabler.building" variant="secondary" class="shadow-sm border border-base-content/5" />
        <x-ui::stat :title="__('admin::ui.dashboard.stats.placement_rate')" :value="$summary['placement_rate'] . '%'" icon="tabler.chart-pie" variant="accent" class="shadow-sm border border-base-content/5" />
        
        @if($isSuperAdmin)
            <x-ui::stat :title="__('admin::ui.dashboard.users.active_sessions')" :value="$userDistribution['active_sessions']" icon="tabler.broadcast" variant="info" class="shadow-sm border border-base-content/5" />
            <x-ui::stat :title="__('admin::ui.dashboard.infrastructure.db_size')" :value="$infrastructure['db_size']" icon="tabler.database" variant="secondary" class="shadow-sm border border-base-content/5" />
            <x-ui::stat :title="__('admin::ui.dashboard.infrastructure.queue_pending')" :value="$infrastructure['queue_pending']" icon="tabler.list-details" :variant="$infrastructure['queue_pending'] > 0 ? 'warning' : 'metadata'" class="shadow-sm border border-base-content/5" />
        @endif
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        {{-- Left Column: Major Activity & Risk --}}
        <div class="lg:col-span-8 flex flex-col gap-6">
            <x-ui::card :title="__('admin::ui.dashboard.recent_assessments')" shadow separator class="bg-base-100/50">
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
                                <span class="font-bold text-xs truncate max-w-[120px]">{{ $reg->student->name }}</span>
                                <span class="text-[9px] opacity-40 uppercase tracking-tighter">{{ $reg->student->username }}</span>
                            </div>
                        </div>
                    @endscope

                    @scope('cell_placement.company_name', $reg)
                        <span class="text-xs opacity-70">{{ $reg->placement?->company_name ?? '-' }}</span>
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
            
            <x-ui::card :title="__('admin::ui.dashboard.at_risk_students')" shadow separator class="bg-base-100/50">
                <x-ui::table :rows="$atRiskStudents" :headers="[
                    ['key' => 'student_name', 'label' => __('admin::ui.dashboard.table.student')],
                    ['key' => 'reason', 'label' => __('admin::ui.dashboard.table.reason')],
                    ['key' => 'risk_level', 'label' => __('admin::ui.dashboard.table.risk_level')],
                ]">
                    @scope('cell_student_name', $item)
                        <span class="text-xs font-medium">{{ $item['student_name'] }}</span>
                    @endscope
                    @scope('cell_reason', $item)
                        <span class="text-[10px] opacity-60 leading-tight">{{ $item['reason'] }}</span>
                    @endscope
                    @scope('cell_risk_level', $item)
                        <x-ui::badge :value="$item['risk_level']" :variant="$item['risk_level'] === 'High' ? 'error' : 'warning'" class="badge-xs font-bold" />
                    @endscope
                </x-ui::table>
            </x-ui::card>
        </div>

        {{-- Right Column: Security, System, Feed --}}
        <div class="lg:col-span-4 flex flex-col gap-6">
            @if($isSuperAdmin)
                <x-ui::card :title="__('admin::ui.dashboard.system_status')" shadow separator class="bg-base-100/50">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="p-3 rounded-2xl bg-base-200/50 flex flex-col">
                            <span class="text-[10px] uppercase opacity-40 font-bold">{{ __('admin::ui.dashboard.security.failed_logins') }}</span>
                            <span @class(['text-lg font-black mt-1', 'text-error' => $securitySummary['failed_logins'] > 0])>{{ $securitySummary['failed_logins'] }}</span>
                        </div>
                        <div class="p-3 rounded-2xl bg-base-200/50 flex flex-col">
                            <span class="text-[10px] uppercase opacity-40 font-bold">{{ __('admin::ui.dashboard.infrastructure.queue_failed') }}</span>
                            <span @class(['text-lg font-black mt-1', 'text-error' => $infrastructure['queue_failed'] > 0])>{{ $infrastructure['queue_failed'] }}</span>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 border-t border-base-content/5 pt-4">
                        <div class="flex justify-between items-center text-xs">
                            <span class="opacity-60">{{ __('admin::ui.dashboard.security.throttled_attempts') }}</span>
                            <span class="font-bold">{{ $securitySummary['throttled_attempts'] }}</span>
                        </div>
                        <div class="flex justify-between items-center text-xs">
                            <span class="opacity-60">{{ __('admin::ui.dashboard.infrastructure.last_backup') }}</span>
                            <span class="font-medium opacity-80">{{ $infrastructure['last_backup'] ?? 'Never' }}</span>
                        </div>
                    </div>
                </x-ui::card>
            @endif

            <x-ui::slot-render name="admin.dashboard.side" />

            <x-ui::card :title="__('admin::ui.dashboard.quick_links')" shadow separator class="bg-base-100/50">
                <div class="grid grid-cols-2 gap-2">
                    <x-ui::button :label="__('admin::ui.dashboard.user_management')" icon="tabler.users" variant="secondary" class="btn-sm text-[10px] uppercase" link="{{ route('admin.students') }}" />
                    <x-ui::button :label="__('admin::ui.dashboard.system_config')" icon="tabler.settings" variant="secondary" class="btn-sm text-[10px] uppercase" link="{{ route('school.settings') }}" />
                </div>
            </x-ui::card>
        </div>
    </div>
</div>
