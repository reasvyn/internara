@props([
    'step',
    'total' => 8,
    'title',
    'description',
    'badgeIcon' => 'tabler.info-circle',
    'badgeText' => null,
])

<div class="max-w-4xl">
    <x-ui::badge variant="metadata" class="mb-12">
        {{ __('setup::wizard.steps', ['current' => $step, 'total' => $total]) }}
    </x-ui::badge>

    <h1 class="text-4xl font-extrabold tracking-tight text-base-content md:text-5xl lg:text-6xl leading-[1.1]">
        {{ $title }}
    </h1>

    <div class="mt-8 space-y-6">
        <p class="text-lg text-base-content/70 leading-relaxed max-w-2xl">
            {{ $description }}
        </p>
        
        @if($badgeText)
            <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary/5 border border-primary/10">
                <x-ui::icon :name="$badgeIcon" class="size-4 text-primary" />
                <span class="text-xs font-bold uppercase tracking-widest text-primary">
                    {{ $badgeText }}
                </span>
            </div>
        @endif
    </div>
</div>