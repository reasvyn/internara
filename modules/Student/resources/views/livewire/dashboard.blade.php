<div>
    <x-ui::header title="{{ __('Dasbor Siswa') }}" subtitle="{{ __('Selamat datang kembali, :name!', ['name' => auth()->user()->name]) }}" />

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @php
                $registration = app(\Modules\Internship\Services\Contracts\RegistrationService::class)->first([
                    'student_id' => auth()->id()
                ]);
            @endphp

            @if($registration)
                {{-- Phase 1: Requirements --}}
                @if(!$registration->hasClearedAllMandatoryRequirements())
                    <div class="alert alert-warning shadow-lg mb-6">
                        <x-ui::icon name="tabler.alert-triangle" />
                        <div>
                            <h3 class="font-bold">{{ __('Persyaratan Belum Lengkap') }}</h3>
                            <div class="text-xs">{{ __('Mohon lengkapi persyaratan administrasi di bawah ini untuk melanjutkan proses magang.') }}</div>
                        </div>
                    </div>
                    
                    <x-ui::slot-render name="student.dashboard.requirements" />

                {{-- Phase 2: Placement & Activity --}}
                @elseif($registration->placement)
                    <x-ui::card title="{{ __('Program Magang Saya') }}" shadow separator>
                        <div class="flex flex-col gap-6">
                            <div class="flex items-center gap-4">
                                <div class="bg-primary/10 p-3 rounded-xl">
                                    <x-ui::icon name="tabler.briefcase" class="w-8 h-8 text-primary" />
                                </div>
                                <div>
                                    <div class="font-bold text-lg">{{ $registration->placement->company_name }}</div>
                                    <div class="text-sm opacity-70">{{ $registration->internship->name }}</div>
                                </div>
                            </div>

                            <hr class="opacity-50">

                            @php
                                $scoreCard = app(\Modules\Assessment\Services\Contracts\AssessmentService::class)->getScoreCard($registration->id);
                            @endphp

                            @if($scoreCard['final_grade'])
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="bg-base-200 p-4 rounded-lg text-center">
                                        <div class="text-xs uppercase opacity-70">{{ __('Nilai Akhir') }}</div>
                                        <div class="text-3xl font-black text-primary">{{ number_format($scoreCard['final_grade'], 2) }}</div>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <x-ui::button label="{{ __('Unduh Sertifikat') }}" icon="tabler.certificate" class="btn-primary btn-sm" link="{{ route('assessment.certificate', $registration->id) }}" />
                                        <x-ui::button label="{{ __('Unduh Transkrip') }}" icon="tabler.file-description" class="btn-outline btn-sm" link="{{ route('assessment.transcript', $registration->id) }}" />
                                    </div>
                                </div>
                            @else
                                <x-ui::alert icon="tabler.info-circle" class="alert-info">
                                    {{ __('Penilaian Anda sedang dalam proses oleh pembimbing.') }}
                                </x-ui::alert>
                            @endif
                        </div>
                    </x-ui::card>

                    <x-ui::slot-render name="student.dashboard.active-content" />
                @else
                    {{-- Requirements Cleared, but Waiting for Placement --}}
                    <x-ui::card title="{{ __('Menunggu Penempatan') }}" class="bg-base-100 border-l-4 border-info">
                        <div class="flex items-center gap-4">
                            <div class="bg-info/10 p-3 rounded-full">
                                <x-ui::icon name="tabler.clock" class="w-6 h-6 text-info" />
                            </div>
                            <div>
                                <p>{{ __('Persyaratan administrasi Anda telah lengkap.') }}</p>
                                <p class="text-sm opacity-70">{{ __('Mohon tunggu admin/koordinator untuk menempatkan Anda di lokasi magang.') }}</p>
                            </div>
                        </div>
                    </x-ui::card>
                @endif
            @else
                <x-ui::alert icon="tabler.alert-triangle" class="alert-warning">
                    {{ __('Anda belum terdaftar dalam program magang aktif.') }}
                </x-ui::alert>
            @endif
        </div>

        <div class="space-y-6">
            @if($registration && $registration->placement)
                <x-ui::slot-render name="student.dashboard.sidebar" />
                
                <x-ui::card title="{{ __('Tautan Cepat') }}" shadow separator>
                    <div class="grid grid-cols-1 gap-2">
                        <x-ui::slot-render name="student.dashboard.quick-actions" />
                    </div>
                </x-ui::card>
            @endif
        </div>
    </div>
</div>
