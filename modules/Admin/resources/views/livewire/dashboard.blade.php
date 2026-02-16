<div>
    <x-ui::header 
        :title="__('admin::ui.dashboard.title')" 
        :subtitle="__('admin::ui.dashboard.subtitle')" 
    />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-8">
        <x-ui::stat :title="__('admin::ui.dashboard.stats.total_interns')" :value="$summary['total_interns']" icon="tabler.users" variant="primary" />
        <x-ui::stat :title="__('admin::ui.dashboard.stats.active_partners')" :value="$summary['active_partners']" icon="tabler.building" variant="secondary" />
        <x-ui::stat :title="__('admin::ui.dashboard.stats.placement_rate')" :value="$summary['placement_rate'] . '%'" icon="tabler.chart-pie" variant="accent" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="lg:col-span-3 flex flex-col gap-8">
            <x-ui::card :title="__('admin::ui.dashboard.recent_assessments')" shadow separator>
                <x-ui::table :rows="$this->registrations" :headers="[
                    ['key' => 'student.name', 'label' => __('admin::ui.dashboard.table.student')],
                    ['key' => 'placement.company_name', 'label' => __('admin::ui.dashboard.table.company')],
                    ['key' => 'final_grade', 'label' => __('admin::ui.dashboard.table.final_grade')],
                    ['key' => 'actions', 'label' => '']
                ]">
                    @scope('cell_student.name', $reg)
                        <div class="flex items-center gap-3">
                            <x-ui::avatar :image="$reg->student->avatar_url" :title="$reg->student->name" size="w-8" />
                            <div>
                                <div class="font-bold text-sm leading-tight">{{ $reg->student->name }}</div>
                                <div class="text-[10px] uppercase tracking-wider opacity-50">{{ $reg->student->username }}</div>
                            </div>
                        </div>
                    @endscope

                    @scope('cell_final_grade', $reg)
                        @php
                            $scoreCard = app(\Modules\Assessment\Services\Contracts\AssessmentService::class)->getScoreCard($reg->id);
                        @endphp
                        @if($scoreCard['final_grade'])
                            <x-ui::badge :value="number_format($scoreCard['final_grade'], 2)" variant="primary" />
                        @else
                            <x-ui::badge :value="__('admin::ui.dashboard.table.not_graded')" variant="secondary" class="badge-sm" />
                        @endif
                    @endscope

                    @scope('actions', $reg)
                        <div class="flex gap-1">
                            <x-ui::button icon="tabler.certificate" variant="tertiary" class="btn-sm" link="{{ route('assessment.certificate', $reg->id) }}" tooltip="{{ __('ui::common.success') }}" />
                            <x-ui::button icon="tabler.file-description" variant="tertiary" class="btn-sm" link="{{ route('assessment.transcript', $reg->id) }}" tooltip="{{ __('ui::common.options') }}" />
                        </div>
                    @endscope
                </x-ui::table>
            </x-ui::card>
            
            <x-ui::card :title="__('admin::ui.dashboard.at_risk_students')" shadow separator class="border-error/20">
                <x-ui::table :rows="$atRiskStudents" :headers="[
                    ['key' => 'student_name', 'label' => __('admin::ui.dashboard.table.student')],
                    ['key' => 'reason', 'label' => __('admin::ui.dashboard.table.reason')],
                    ['key' => 'risk_level', 'label' => __('admin::ui.dashboard.table.risk_level')],
                ]">
                    @scope('cell_risk_level', $item)
                        <x-ui::badge :value="$item['risk_level']" :variant="$item['risk_level'] === 'High' ? 'error' : 'warning'" class="badge-sm" />
                    @endscope
                </x-ui::table>
            </x-ui::card>
        </div>

        <div class="lg:col-span-1 flex flex-col gap-6">
            <x-ui::card :title="__('admin::ui.dashboard.quick_links')" shadow separator>
                <div class="flex flex-col gap-1">
                    <x-ui::button :label="__('admin::ui.dashboard.user_management')" icon="tabler.users" variant="tertiary" class="justify-start w-full" link="{{ route('admin.students') }}" />
                    <x-ui::button :label="__('admin::ui.dashboard.system_config')" icon="tabler.settings" variant="tertiary" class="justify-start w-full" link="{{ route('school.settings') }}" />
                </div>
            </x-ui::card>

            <x-ui::slot-render name="admin.dashboard.side" />
        </div>
    </div>
</div>
