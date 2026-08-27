<div class="mx-auto mt-6 max-w-3xl">
    <x-ui::ui.page-header
        :title="__('certificate.my_certificates')"
        :subtitle="__('certificate.my_certificates_subtitle')"
    />

    @forelse ($certificates as $certificate)
        <x-ts-card shadowless class="mb-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-semibold">{{ $certificate->certificate_number }}</h3>
                    <p class="text-base-content/60 text-sm">{{ $certificate->registration?->internship?->name }}</p>
                    <p class="text-base-content/40 text-xs">
                        {{ __('certificate.issued_at') }}: {{ $certificate->issued_at?->format('d M Y') }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('certificates.download', $certificate) }}" target="_blank">
                        <x-ts-button icon="document-arrow-down" color="primary" sm :text="__('certificate.download')" />
                    </a>
                </div>
            </div>

    @empty
        <x-ts-card shadowless>
            <div class="p-6 text-center">
                <x-ts-icon name="document" class="text-base-content/40 mx-auto mb-3 h-12 w-12" />
                <p class="text-base-content/60">{{ __('certificate.no_certificates') }}</p>
            </div>

    @endforelse
</div>
