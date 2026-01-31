<div>
    <x-ui::header title="{{ __('Kesiapan Kelulusan') }}" subtitle="{{ __('Verifikasi pemenuhan syarat kelulusan magang siswa.') }}" />

    <x-ui::main>
        <div class="mb-4">
            <x-ui::input icon="tabler.search" placeholder="{{ __('Cari siswa...') }}" wire:model.live.debounce="search" />
        </div>

        <x-ui::card>
            <x-ui::table :headers="[
                ['key' => 'student.name', 'label' => __('Siswa')],
                ['key' => 'student.username', 'label' => __('Username')],
                ['key' => 'placement.company_name', 'label' => __('Tempat Magang')],
                ['key' => 'readiness', 'label' => __('Status Kesiapan')],
                ['key' => 'actions', 'label' => ''],
            ]" :rows="$registrations" with-pagination>
                
                @scope('cell_readiness', $reg)
                    @php $readiness = $this->getReadiness($reg->id); @endphp
                    @if($readiness['is_ready'])
                        <x-ui::badge label="{{ __('Siap Lulus') }}" class="badge-success" />
                    @else
                        <div class="flex flex-col gap-1">
                            <x-ui::badge label="{{ __('Belum Siap') }}" class="badge-warning" />
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
                            <x-ui::button icon="tabler.certificate" class="btn-ghost btn-sm text-primary" link="{{ route('assessment.certificate', $reg->id) }}" tooltip="{{ __('Sertifikat') }}" external />
                            <x-ui::button icon="tabler.file-download" class="btn-ghost btn-sm text-success" link="{{ route('assessment.transcript', $reg->id) }}" tooltip="{{ __('Transkrip') }}" external />
                        @else
                            <x-ui::button icon="tabler.eye" class="btn-ghost btn-sm" link="{{ route('teacher.assess', $reg->id) }}" tooltip="{{ __('Lihat Detail') }}" />
                        @endif
                    </div>
                @endscope
            </x-ui::table>
        </x-ui::card>
    </x-ui::main>
</div>
