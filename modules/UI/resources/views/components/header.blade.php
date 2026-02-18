@props([
    'title' => null,
    'subtitle' => null,
    'context' => null,
    'middle' => null, 
    'actions' => null,
    'separator' => false,
])

<div class="mb-10">
    @if($context)
        <div class="mb-1 flex items-center gap-2 text-[10px] font-black uppercase tracking-[0.15em] text-base-content/40">
            <span>{{ setting('brand_name', 'Internara') }}</span>
            <x-ui::icon name="tabler.chevron-right" class="size-2.5" />
            <span>{{ __($context) }}</span>
        </div>
    @endif

    <x-mary-header 
        {{ $attributes->merge(['class' => '']) }}
        :title="$title"
        :subtitle="$subtitle"
        :separator="$separator"
    >
        @if($middle)
            <x-slot:middle>
                {{ $middle }}
            </x-slot:middle>
        @endif

        @if($actions)
            <x-slot:actions>
                <div class="flex items-center gap-3">
                    {{ $actions }}
                </div>
            </x-slot:actions>
        @endif
    </x-mary-header>
</div>
