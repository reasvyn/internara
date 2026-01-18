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
        ]" :rows="$this->students">
            @scope('cell_status', $registration)
                <x-ui::badge :label="$registration->getStatusLabel()" :class="'badge-' . $registration->getStatusColor()" />
            @endscope
            
            @scope('actions', $registration)
                <x-ui::button label="{{ __('Detail') }}" icon="tabler.eye" class="btn-sm btn-ghost" link="#" />
            @endscope
        </x-ui::table>
    </x-ui::card>
</div>
