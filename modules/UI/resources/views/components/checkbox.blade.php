@props([
    'label' => null,
    'hint' => null,
    'aos' => null,
])

<div class="form-control" :data-aos="$aos">
    <label class="label cursor-pointer justify-start gap-4 min-h-[2.75rem]">
        <x-mary-checkbox 
            {{ $attributes->merge(['class' => 'checkbox checkbox-accent']) }} 
        />
        
        @if($label)
            <div class="flex flex-col">
                <span class="label-text font-medium text-base-content/90">{{ $label }}</span>
                @if($hint)
                    <span class="label-text-alt text-base-content/60">{{ $hint }}</span>
                @endif
            </div>
        @endif
    </label>
</div>
