<div>
    <x-ui::header 
        :title="__('admin::ui.menu.readiness')" 
        :subtitle="__('admin::ui.dashboard.graduation_readiness_subtitle', 'Verifikasi pemenuhan syarat kelulusan magang siswa.')" 
    />

    <div class="mb-4">
            <x-ui::input icon="tabler.search" :placeholder="__('internship::ui.search_registration')" wire:model.live.debounce="search" />
        </div>

        <x-ui::card>
            <x-ui::table :headers="[
                ['key' => 'student.name', 'label' => __('admin::ui.readiness.student')],
                ['key' => 'student.username', 'label' => __('admin::ui.readiness.username')],
                ['key' => 'placement.company_name', 'label' => __('admin::ui.readiness.placement')],
                ['key' => 'readiness', 'label' => __('admin::ui.readiness.status')],
                ['key' => 'actions', 'label' => ''],
            ]" :rows="$registrations" with-pagination>
                
                @scope('cell_readiness', $reg)
                    @php $readiness = $this->getReadiness($reg->id); @endphp
                    @if($readiness['is_ready'])
                        <x-ui::badge label="{{ __('admin::ui.readiness.ready') }}" class="badge-success" />
                    @else
                        <div class="flex flex-col gap-1">
                            <x-ui::badge label="{{ __('admin::ui.readiness.not_ready') }}" class="badge-warning" />
                            <div class="text-[10px] opacity-70 line-clamp-1" title="{{ implode(', ', $readiness['missing']) }}">
                                {{ implode(', ', $readiness['missing']) }}
                            </div>
                        </div>
                    @endif
                @endscope

                @scope('actions', $reg)
                    <div class="flex gap-2">
                        @php $readiness = $this->getReadiness($reg->id); @endphp
                        @if($readiness['is_ready'])
                            <x-ui::button icon="tabler.certificate" class="btn-ghost btn-sm text-primary" link="{{ route('assessment.certificate', $reg->id) }}" tooltip="{{ __('admin::ui.readiness.certificate') }}" external />
                            <x-ui::button icon="tabler.file-download" class="btn-ghost btn-sm text-success" link="{{ route('assessment.transcript', $reg->id) }}" tooltip="{{ __('admin::ui.readiness.transcript') }}" external />
                        @else
                            <x-ui::button icon="tabler.eye" class="btn-ghost btn-sm" link="{{ route('teacher.assess', $reg->id) }}" tooltip="{{ __('admin::ui.readiness.view_detail') }}" />
                        @endif
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
</div>
