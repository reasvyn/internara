@props([
    'header' => null,
    'content' => null,
    'step' => 1,
    'totalSteps' => 7,
])

@php
$steps = [
    1 => ['key' => 'welcome', 'label' => __('setup::wizard.step_labels.welcome'), 'icon' => 'tabler-home'],
    2 => ['key' => 'school', 'label' => __('setup::wizard.step_labels.school'), 'icon' => 'tabler-building'],
    3 => ['key' => 'account', 'label' => __('setup::wizard.step_labels.account'), 'icon' => 'tabler-shield-check'],
    4 => ['key' => 'department', 'label' => __('setup::wizard.step_labels.department'), 'icon' => 'tabler-users'],
    5 => ['key' => 'internship', 'label' => __('setup::wizard.step_labels.internship'), 'icon' => 'tabler-calendar'],
    6 => ['key' => 'system', 'label' => __('setup::wizard.step_labels.system'), 'icon' => 'tabler-mail'],
    7 => ['key' => 'complete', 'label' => __('setup::wizard.step_labels.complete'), 'icon' => 'tabler-check'],
];
@endphp

<div class="min-h-screen flex flex-col bg-base-100 dark:bg-base-300">
    <!-- Progress Bar with Step Navigation -->
    <div class="sticky top-0 z-50 bg-base-100/80 dark:bg-base-300/80 backdrop-blur-md border-b border-base-200/50 dark:border-base-200/20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex items-center justify-between gap-4">
                <span class="text-xs font-medium text-base-content/50 dark:text-base-content/40">
                    {{ __('setup::wizard.steps', ['current' => $step, 'total' => $totalSteps]) }}
                </span>
                <div class="flex-1 max-w-[30%] h-1.5 bg-base-200/50 dark:bg-base-200/30 rounded-full overflow-hidden">
                    <div class="h-full bg-base-content dark:bg-primary transition-all duration-500 ease-out" 
                         style="width: {{ ($step / $totalSteps) * 100 }}%">
                    </div>
                </div>
                <!-- Mobile: Show current step badge -->
                <span class="text-xs text-base-content/40 dark:text-base-content/30 md:hidden">
                    {{ $steps[$step]['label'] }}
                </span>
            </div>
            
            <!-- Desktop: Step Dots Navigation -->
            <div class="hidden md:flex items-center justify-center gap-1 mt-3 pt-3 border-t border-base-200/30 dark:border-base-200/20">
                @foreach($steps as $num => $stepInfo)
                    @php
                    $isCompleted = $num < $step;
                    $isCurrent = $num === $step;
                    @endphp
                    <button 
                        wire:click="goToStep('{{ $stepInfo['key'] }}')"
                        @disabled($num > $step)
                        class="group flex items-center gap-2 px-2 py-1 rounded-md text-xs transition-all duration-200
                            {{ $isCompleted ? 'text-base-content/50 hover:text-base-content hover:bg-base-200/30' : '' }}
                            {{ $isCurrent ? 'text-base-content font-medium bg-base-200/40' : '' }}
                            {{ $num > $step ? 'text-base-content/30 cursor-not-allowed' : '' }}"
                        title="{{ $stepInfo['label'] }}"
                    >
                        <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px]
                            {{ $isCompleted ? 'bg-primary text-primary-content' : '' }}
                            {{ $isCurrent ? 'bg-primary text-primary-content ring-2 ring-primary/20' : '' }}
                            {{ $num > $step ? 'bg-base-200 text-base-content/40' : '' }}">
                            @if($isCompleted)
                                <x-ui::icon name="tabler.check" class="size-3" />
                            @else
                                {{ $num }}
                            @endif
                        </span>
                        <span class="hidden lg:inline">{{ $stepInfo['label'] }}</span>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col">
        <div class="flex-1 max-w-3xl mx-auto w-full px-4 sm:px-6 lg:px-8 py-8 md:py-12">
            <!-- Header -->
            <header class="mb-8 md:mb-10">
                {{ $header }}
            </header>
 
            <!-- Content Card -->
            @isset($content)
                <div class="bg-base-100 dark:bg-base-200 rounded-2xl">
                    {{ $content }}
                </div>
            @endisset
        </div>
    </div>

    <!-- Footer Actions -->
    @isset($footer)
	    <div class="sticky bottom-0 bg-base-100/80 dark:bg-base-300/80 backdrop-blur-md border-t border-base-200/50 dark:border-base-200/20">
	        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
	            {{ $footer }}
	        </div>
	    </div>
    @endisset
</div>
