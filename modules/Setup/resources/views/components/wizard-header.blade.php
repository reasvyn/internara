@props([
    'step',
    'total' => 7,
    'title',
    'description',
    'badgeIcon' => 'tabler.check-circle',
    'badgeText' => null,
])

<div>
    <!-- Badge -->
    <div class="flex items-center gap-2 mb-4">
        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-base-200/60 dark:bg-base-200/20 text-xs font-medium text-base-content/60 dark:text-base-content/50">
            {{ __('setup::wizard.steps', ['current' => $step, 'total' => $total]) }}
        </span>
        @if($badgeText)
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-base-content/5 dark:bg-base-content/10 text-xs font-medium text-base-content/70 dark:text-base-content/60">
                <x-ui::icon :name="$badgeIcon" class="size-3" />
                {{ $badgeText }}
            </span>
        @endif
    </div>

    <!-- Title -->
    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-base-content dark:text-base-content/90 leading-tight mb-3">
        {{ $title }}
    </h1>

    <!-- Description -->
    @isset($description)
        <p class="text-base text-base-content/60 dark:text-base-content/50 leading-relaxed max-w-2xl">
            {{ $description }}
        </p>
    @endisset
</div>