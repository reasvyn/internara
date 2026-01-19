<div>
    <x-ui::header title="{{ __('Dasbor Admin') }}" subtitle="{{ __('Selamat datang di panel administrasi Internara.') }}" />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">
        <div class="lg:col-span-3">
            <x-ui::card title="{{ __('Penilaian Magang Terbaru') }}" shadow separator>
                @php
                    $registrations = app(\Modules\Internship\Services\Contracts\InternshipRegistrationService::class)->paginate(10);
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
