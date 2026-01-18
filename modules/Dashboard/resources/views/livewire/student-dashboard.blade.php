<div>
    <x-ui::header title="{{ __('Dasbor Siswa') }}" subtitle="{{ __('Selamat datang kembali, :name!', ['name' => auth()->user()->name]) }}" />

    <x-ui::main>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                {{-- Quick Info or stats could go here --}}
                <x-ui::card title="{{ __('Program Magang Saya') }}" shadow separator>
                    @php
                        $registration = app(\Modules\Internship\Services\Contracts\InternshipRegistrationService::class)->first([
                            'student_id' => auth()->id(),
                            'latest_status' => 'active'
                        ]);
                    @endphp

                    @if($registration)
                        <div class="flex items-center gap-4">
                            <div class="bg-primary/10 p-3 rounded-xl">
                                <x-ui::icon name="tabler.briefcase" class="w-8 h-8 text-primary" />
                            </div>
                            <div>
                                <div class="font-bold text-lg">{{ $registration->placement->company_name }}</div>
                                <div class="text-sm opacity-70">{{ $registration->internship->name }}</div>
                            </div>
                        </div>
                    @else
                        <x-ui::alert icon="tabler.alert-triangle" class="alert-warning">
                            {{ __('Anda belum terdaftar dalam program magang aktif.') }}
                        </x-ui::alert>
                    @endif
                </x-ui::card>
            </div>

            <div class="space-y-6">
                @livewire('attendance::attendance-manager')
                
                <x-ui::card title="{{ __('Tautan Cepat') }}" shadow separator>
                    <div class="grid grid-cols-1 gap-2">
                        <x-ui::button label="{{ __('Log Presensi') }}" icon="tabler.calendar" class="btn-ghost justify-start" link="{{ route('attendance.index') }}" />
                        <x-ui::button label="{{ __('Jurnal Harian') }}" icon="tabler.book" class="btn-ghost justify-start" link="{{ route('journal.index') }}" />
                    </div>
                </x-ui::card>
            </div>
        </div>
    </x-ui::main>
</div>