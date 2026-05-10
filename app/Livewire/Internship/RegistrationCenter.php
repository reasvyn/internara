<?php

declare(strict_types=1);

namespace App\Livewire\Internship;

use App\Models\Internship;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RegistrationCenter extends Component
{
    #[Computed]
    public function openInternships(): Collection
    {
        return Internship::query()
            ->whereIn('status', ['published', 'active'])
            ->where(function ($q) {
                $q->whereNull('registration_start_date')
                    ->orWhere('registration_start_date', '<=', now()->toDateString());
            })
            ->where(function ($q) {
                $q->whereNull('registration_end_date')
                    ->orWhere('registration_end_date', '>=', now()->toDateString());
            })
            ->orderBy('registration_end_date')
            ->get();
    }

    public function render()
    {
        return <<<'HTML'
        <div>
            <x-mary-header title="Pendaftaran PKL" subtitle="Daftar program PKL yang sedang menerima pendaftaran" separator />

            @if($this->openInternships->isEmpty())
                <div class="flex flex-col items-center justify-center py-24 text-center">
                    <x-mary-icon name="o-x-circle" class="size-20 text-base-300 mb-6" />
                    <h2 class="text-2xl font-black text-base-content/60 mb-2">Tidak Ada Pendaftaran Terbuka</h2>
                    <p class="text-base-content/40 max-w-md">
                        Saat ini belum ada program PKL yang membuka pendaftaran. Silakan periksa kembali nanti.
                    </p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($this->openInternships as $internship)
                        <x-mary-card class="border border-base-200 hover:shadow-lg transition-shadow">
                            <x-mary-badge value="{{ __('internship.statuses.' . $internship->status->value) }}" class="badge-info mb-3" />
                            <h3 class="text-lg font-bold mb-2">{{ $internship->name }}</h3>
                            <div class="text-sm text-base-content/60 space-y-1 mb-4">
                                <p>
                                    <x-mary-icon name="o-calendar" class="size-4 inline" />
                                    {{ $internship->start_date->format('d M Y') }} – {{ $internship->end_date->format('d M Y') }}
                                </p>
                                @if($internship->registration_start_date || $internship->registration_end_date)
                                    <p class="text-primary font-medium">
                                        <x-mary-icon name="o-clock" class="size-4 inline" />
                                        Pendaftaran: {{ $internship->registration_start_date?->format('d M Y') ?? '–' }} – {{ $internship->registration_end_date?->format('d M Y') ?? '–' }}
                                    </p>
                                @endif
                            </div>

                            @auth
                                @role('student')
                                    <x-mary-button
                                        label="Daftar Sekarang"
                                        icon="o-arrow-right"
                                        class="btn-primary btn-sm w-full"
                                        link="{{ route('student.internships.register') }}"
                                        wire:navigate />
                                @else
                                    <x-mary-button
                                        label="Lihat Detail"
                                        icon="o-eye"
                                        class="btn-ghost btn-sm w-full"
                                        disabled />
                                @endrole
                            @else
                                <x-mary-button
                                    label="Daftar (Belum Punya Akun)"
                                    icon="o-user-plus"
                                    class="btn-primary btn-sm w-full"
                                    link="{{ route('apply') }}"
                                    wire:navigate />
                            @endauth
                        </x-mary-card>
                    @endforeach
                </div>
            @endif
        </div>
        HTML;
    }
}
