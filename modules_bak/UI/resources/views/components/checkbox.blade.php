@props([
    'label' => null,
    'hint' => null,
    'labelClass' => '',
])

<div class="form-control">
    <label class="label cursor-pointer justify-start gap-3 min-h-[2.75rem]">
        <x-mary-checkbox 
            {{ $attributes->merge(['class' => 'checkbox ' . ($attributes->get('class') && str_contains($attributes->get('class'), 'checkbox-') ? '' : 'checkbox-primary'), 'aria-label' => $label]) }} 
        />
        
        @if($label)
            <div class="flex flex-col">
                <span class="label-text font-medium text-base-content/90 {{ $labelClass }}">{{ $label }}</span>
                @if($hint)
                    <span class="label-text-alt text-base-content/60">{{ $hint }}</span>
                @endif
            </div>
        @endif
    </label>
</div>
