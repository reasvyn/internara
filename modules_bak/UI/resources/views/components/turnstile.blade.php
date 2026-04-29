@props(['fieldName' => 'cf-turnstile-response'])

@php
    $siteKey = config('services.cloudflare.turnstile.site_key');
@endphp

<div 
    x-data="{
        siteKey: '{{ $siteKey }}',
        initTurnstile() {
            if (!this.siteKey) {
                if (isDebugMode()) console.warn('Turnstile: Site key is missing. Skipping render.');
                return;
            }

            if (window.turnstile) {
                turnstile.render($refs.turnstile, {
                    sitekey: this.siteKey,
                    callback: (token) => {
                        $wire.set('{{ $fieldName }}', token);
                    },
                });
            }
        }
    }"
    x-init="initTurnstile()"
    wire:ignore
    {{ $attributes->class(['flex justify-center my-4']) }}
>
    <div x-ref="turnstile"></div>
    
    @error($fieldName)
        <span class="text-error text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

@once
    @push('scripts')
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endpush
@endonce
