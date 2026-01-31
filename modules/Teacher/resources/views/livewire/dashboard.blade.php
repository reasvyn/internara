<div>
    <x-ui::header title="{{ __('Dasbor Guru') }}" subtitle="{{ __('Pantau aktivitas dan kehadiran siswa bimbingan Anda.') }}" />

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-8">
        <x-ui::card class="bg-primary text-primary-content">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm opacity-80">{{ __('Total Siswa Bimbingan') }}</div>
                    <div class="text-3xl font-bold">{{ $this->students->count() }}</div>
                </div>
                <x-ui::icon name="tabler.users" class="w-10 h-10 opacity-20" />
            </div>
        </x-ui::card>
    </div>

    <x-ui::card title="{{ __('Siswa Bimbingan') }}">
        <x-ui::table :headers="[
            ['key' => 'student.name', 'label' => __('Nama Siswa')],
            ['key' => 'placement.company_name', 'label' => __('Tempat Magang')],
            ['key' => 'status', 'label' => __('Status')],
            ['key' => 'readiness', 'label' => __('Kesiapan Lulus'), 'sortable' => false],
        ]" :rows="$this->students">
            @scope('cell_status', $registration)
                <x-ui::badge :label="$registration->getStatusLabel()" :class="'badge-' . $registration->getStatusColor()" />
            @endscope

            @scope('cell_readiness', $registration)
                @php $readiness = $this->getReadiness($registration->id); @endphp
                @if($readiness['is_ready'])
                    <x-ui::badge label="{{ __('Siap Lulus') }}" class="badge-success" />
                @else
                    <div class="tooltip" data-tip="{{ implode(', ', $readiness['missing']) }}">
                        <x-ui::badge label="{{ __('Belum Siap') }}" class="badge-warning" />
                    </div>
                @endif
            @endscope
            
            @scope('actions', $registration)
                <x-ui::button label="{{ __('Supervisi') }}" icon="tabler.messages" class="btn-sm btn-ghost text-primary" link="{{ route('teacher.mentoring', $registration->id) }}" />
                <x-ui::button label="{{ __('Assess') }}" icon="tabler.clipboard-check" class="btn-sm btn-ghost" link="{{ route('teacher.assess', $registration->id) }}" />
                
                @php $readiness = $this->getReadiness($registration->id); @endphp
                @if($readiness['is_ready'])
                    <x-ui::button label="{{ __('Transkrip') }}" icon="tabler.file-download" class="btn-sm btn-ghost text-success" link="{{ route('assessment.transcript', $registration->id) }}" external />
                @endif
            @endscope
        </x-ui::table>
    </x-ui::card>
</div>
