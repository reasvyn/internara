@props(['fieldName' => 'cf-turnstile-response'])

<div 
    x-data="{
        initTurnstile() {
            if (window.turnstile) {
                turnstile.render($refs.turnstile, {
                    sitekey: '{{ config('services.cloudflare.turnstile.site_key') }}',
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
