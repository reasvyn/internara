<x-shared::ui.page-header :title="__('certificate.my_certificates')" :subtitle="__('certificate.my_certificates_subtitle')" />

<div class="max-w-3xl mx-auto mt-6">
    @forelse($certificates as $certificate)
        <x-mary-card class="mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold">{{ $certificate->certificate_number }}</h3>
                    <p class="text-sm text-base-content/60">{{ $certificate->registration?->internship?->name }}</p>
                    <p class="text-xs text-base-content/40">{{ __('certificate.issued_at') }}: {{ $certificate->issued_at?->format('d M Y') }}</p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('certificates.download', $certificate) }}" target="_blank">
                        <x-mary-button icon="o-document-arrow-down" class="btn-primary btn-sm"
                            :label="__('certificate.download')" />
                    </a>
                </div>
            </div>
        </x-mary-card>
    @empty
        <x-mary-card>
            <div class="p-6 text-center">
                <x-mary-icon name="o-document" class="text-base-content/40 w-12 h-12 mx-auto mb-3" />
                <p class="text-base-content/60">{{ __('certificate.no_certificates') }}</p>
            </div>
        </x-mary-card>
    @endforelse
</div>
