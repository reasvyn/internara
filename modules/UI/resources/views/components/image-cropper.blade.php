@props([
    'id' => 'global-image-cropper',
])

<div x-data="imageCropper()">
    <template x-teleport="body">
        <div
            class="fixed inset-0 z-[10000] overflow-y-auto bg-base-300/60 backdrop-blur-md flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
        >
            {{-- Modal Box --}}
            <div 
                class="relative w-full max-w-2xl transform overflow-hidden rounded-[2.5rem] bg-base-100 p-6 text-left align-middle shadow-2xl transition-all border border-base-200 lg:p-10"
            >
                {{-- Header --}}
                <div class="mb-8">
                    <div class="flex items-start justify-between gap-6">
                        <div class="flex-1">
                            <h3 class="text-3xl font-black tracking-tight text-base-content">{{ __('Sesuaikan Gambar') }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-base-content/60">{{ __('DEBUG MODE: Komponen muncul tanpa pemicu untuk verifikasi.') }}</p>
                        </div>
                    </div>
                    <div class="divider my-6 opacity-10"></div>
                </div>

                {{-- Cropping Area --}}
                <div class="relative min-h-[450px] w-full overflow-hidden rounded-3xl border border-base-200 bg-base-300 shadow-inner flex items-center justify-center">
                    <p class="text-base-content/20 font-bold uppercase tracking-widest">{{ __('Area Cropping') }}</p>
                    <img x-ref="image" class="hidden" />
                </div>

                {{-- Actions --}}
                <div class="mt-10 flex justify-end gap-3">
                    <button type="button" class="btn btn-ghost px-10 font-bold text-base-content/60">{{ __('Batal') }}</button>
                    <button type="button" class="btn btn-primary px-12 font-bold shadow-xl shadow-primary/20">
                        {{ __('Simpan Potongan') }}
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
