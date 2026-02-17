@props([
    'id' => \Illuminate\Support\Str::random(10),
    'title' => null,
    'subtitle' => null,
    'separator' => true,
])

@php
    $wireModel = $attributes->wire('model');
@endphp

<div 
    x-data="{ 
        show: @if($wireModel->value()) @entangle($wireModel) @else false @endif 
    }"
    @keydown.escape.window="show = false"
    class="hidden"
>
    @teleport('body')
        <div 
            x-show="show" 
            class="fixed inset-0 z-[9999] overflow-y-auto"
            style="display: none;"
            role="dialog"
            aria-modal="true"
        >
            {{-- Backdrop --}}
            <div 
                x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-base-300/60 backdrop-blur-md" 
                @click="show = false"
            ></div>

            {{-- Modal Box Container (Handles Centering) --}}
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div 
                    x-show="show"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-8 sm:translate-y-0 sm:scale-90"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-8 sm:translate-y-0 sm:scale-90"
                    class="relative w-full max-w-2xl transform overflow-hidden rounded-3xl bg-base-100 p-6 text-left align-middle shadow-2xl transition-all border border-base-200 lg:p-10"
                    @click.stop
                >
                    {{-- Header --}}
                    @if($title)
                        <div class="mb-8">
                            <div class="flex items-start justify-between gap-6">
                                <div class="flex-1">
                                    <h3 class="text-3xl font-black tracking-tight text-base-content">{{ $title }}</h3>
                                    @if($subtitle)
                                        <p class="mt-2 text-sm leading-relaxed text-base-content/60">{{ $subtitle }}</p>
                                    @endif
                                </div>
                                <button 
                                    @click="show = false" 
                                    class="btn btn-ghost btn-circle btn-sm -mt-2 -mr-2"
                                    aria-label="{{ __('ui::common.close') }}"
                                >
                                    <x-ui::icon name="tabler.x" class="size-6 opacity-40" />
                                </button>
                            </div>
                            
                            @if($separator)
                                <div class="divider my-6 opacity-10"></div>
                            @endif
                        </div>
                    @endif

                    {{-- Content --}}
                    <div class="relative">
                        {{ $slot }}
                    </div>
                    
                    {{-- Actions --}}
                    @isset($actions)
                        <div class="mt-12 flex items-center justify-end gap-3">
                            {{ $actions }}
                        </div>
                    @endisset
                </div>
            </div>
        </div>
    @endteleport
</div>