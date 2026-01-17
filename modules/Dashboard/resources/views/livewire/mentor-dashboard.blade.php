<div>
    <x-ui::header title="{{ __('Dasbor Mentor Industri') }}" subtitle="{{ __('Pantau aktivitas dan kehadiran siswa magang di perusahaan Anda.') }}" />

    <x-ui::main>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3 mb-8">
            <x-ui::card class="bg-secondary text-secondary-content">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-sm opacity-80">{{ __('Total Siswa Magang') }}</div>
                        <div class="text-3xl font-bold">{{ $this->students->count() }}</div>
                    </div>
                    <x-ui::icon name="o-academic-cap" class="w-10 h-10 opacity-20" />
                </div>
            </x-ui::card>
        </div>

        <x-ui::card title="{{ __('Siswa Magang') }}">
            <x-ui::table :headers="[
                ['key' => 'student.name', 'label' => __('Nama Siswa')],
                ['key' => 'internship.title', 'label' => __('Program Magang')],
                ['key' => 'status', 'label' => __('Status')],
            ]" :rows="$this->students">
                @scope('cell_status', $registration)
                    <x-ui::badge :label="$registration->getStatusLabel()" :class="'badge-' . $registration->getStatusColor()" />
                @endscope
                
                @scope('actions', $registration)
                    <x-ui::button label="{{ __('Detail') }}" icon="o-eye" class="btn-sm btn-ghost" link="#" />
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>
</div>
