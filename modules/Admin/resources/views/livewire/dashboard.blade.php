<div>
    <x-ui::header title="{{ __('Dasbor Admin') }}" subtitle="{{ __('Selamat datang di panel administrasi Internara.') }}" />

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3 mb-6">
        <x-ui::stat title="{{ __('Total Siswa Magang') }}" value="{{ $summary['total_interns'] }}" icon="tabler.users" />
        <x-ui::stat title="{{ __('Mitra Industri Aktif') }}" value="{{ $summary['active_partners'] }}" icon="tabler.building" />
        <x-ui::stat title="{{ __('Tingkat Penempatan') }}" value="{{ $summary['placement_rate'] }}%" icon="tabler.chart-pie" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="lg:col-span-3 flex flex-col gap-6">
            <x-ui::card title="{{ __('Penilaian Magang Terbaru') }}" shadow separator>
                @php
                    $registrations = app(\Modules\Internship\Services\Contracts\RegistrationService::class)->paginate(10);
                @endphp

                <x-ui::table :rows="$registrations" :headers="[
                    ['key' => 'student.name', 'label' => __('Siswa')],
                    ['key' => 'company_name', 'label' => __('Perusahaan')],
                    ['key' => 'final_grade', 'label' => __('Nilai Akhir')],
                    ['key' => 'actions', 'label' => '']
                ]">
                    @scope('cell_student.name', $reg)
                        <div class="font-bold">{{ $reg->student->name }}</div>
                        <div class="text-xs opacity-70">{{ $reg->student->username }}</div>
                    @endscope

                    @scope('cell_final_grade', $reg)
                        @php
                            $scoreCard = app(\Modules\Assessment\Services\Contracts\AssessmentService::class)->getScoreCard($reg->id);
                        @endphp
                        @if($scoreCard['final_grade'])
                            <x-ui::badge value="{{ number_format($scoreCard['final_grade'], 2) }}" class="badge-primary" />
                        @else
                            <x-ui::badge value="{{ __('Belum Dinilai') }}" class="badge-ghost" />
                        @endif
                    @endscope

                    @scope('actions', $reg)
                        <div class="flex gap-2">
                            <x-ui::button icon="tabler.certificate" class="btn-ghost btn-sm" link="{{ route('assessment.certificate', $reg->id) }}" tooltip="{{ __('Sertifikat') }}" />
                            <x-ui::button icon="tabler.file-description" class="btn-ghost btn-sm" link="{{ route('assessment.transcript', $reg->id) }}" tooltip="{{ __('Transkrip') }}" />
                        </div>
                    @endscope
                </x-ui::table>
            </x-ui::card>
            
            <x-ui::card title="{{ __('Siswa Dalam Pantauan (At-Risk)') }}" shadow separator class="border-error">
                <x-ui::table :rows="$atRiskStudents" :headers="[
                    ['key' => 'student_name', 'label' => __('Nama Siswa')],
                    ['key' => 'reason', 'label' => __('Penyebab')],
                    ['key' => 'risk_level', 'label' => __('Tingkat Risiko')],
                ]">
                    @scope('cell_risk_level', $item)
                        <x-ui::badge :value="$item['risk_level']" :class="$item['risk_level'] === 'High' ? 'badge-error' : 'badge-warning'" />
                    @endscope
                </x-ui::table>
            </x-ui::card>
        </div>

        <div class="lg:col-span-1">
            <x-ui::card title="{{ __('Tautan Cepat') }}" shadow separator>
                <div class="flex flex-col gap-2">
                    <x-ui::button label="{{ __('Manajemen User') }}" icon="tabler.users" class="btn-ghost justify-start" link="#" />
                    <x-ui::button label="{{ __('Konfigurasi Sistem') }}" icon="tabler.settings" class="btn-ghost justify-start" link="#" />
                </div>
            </x-ui::card>
        </div>
    </div>
</div>
