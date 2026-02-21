@props([
    'label' => null,
    'name' => 'file',
    'id' => null,
    'accept' => 'image/*',
    'preview' => null,
    'multiple' => false,
    'hint' => null,
    'placeholder' => null,
    'ratio' => 1,
    'crop' => true,
])

@php
    $id = $id ?? $name . '_' . Str::random(5);
    $isMultiple = filter_var($multiple, FILTER_VALIDATE_BOOLEAN);
    $isCrop = filter_var($crop, FILTER_VALIDATE_BOOLEAN);
    
    // Wire model for AlpineJS
    $model = $attributes->get('wire:model') ?: ($attributes->get('wire:model.live') ?: $attributes->get('wire:model.blur'));
@endphp

<div
    x-data="fileComponent({
        model: @js($model),
        preview: @js($preview),
        ratio: @js($ratio),
        isCrop: @js($isCrop),
        isMultiple: @js($isMultiple)
    })"
    class="w-full"
    @dragover.prevent="isDropping = true"
    @dragenter.prevent="isDropping = true"
    @dragleave.prevent="isDropping = false"
    @drop.prevent="handleDrop($event)"
>
    @isset($label)
        <label class="label mb-2 px-1 font-bold text-base-content/70" for="{{ $id }}">
            {{ $label }}
        </label>
    @endisset

    {{-- Native Hidden Input --}}
    <input
        type="file"
        id="{{ $id }}"
        class="hidden"
        accept="{{ $accept }}"
        x-ref="input"
        x-on:change="handleSelect"
    />

    {{-- Aesthetic Dropzone --}}
    <div 
        class="relative flex min-h-[160px] w-full cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed bg-base-100 p-8 transition-all duration-300 shadow-sm"
        x-on:click="$refs.input.click()"
        :class="isDropping ? 'border-accent bg-accent/5 scale-[1.01]' : 'border-base-300 hover:border-accent/50 hover:bg-base-200'"
    >
        {{-- Empty State Content --}}
        <div x-show="files.length === 0" class="flex flex-col items-center gap-4 text-center">
            <div class="flex size-14 items-center justify-center rounded-xl bg-base-200 text-base-content/40">
                <x-tabler-cloud-upload class="size-8" />
            </div>
            <div class="space-y-1">
                <p class="text-sm font-bold text-base-content/80">
                    <span>{{ $placeholder ?? __('ui::file.instruction') }}</span>
                    <span class="text-accent underline underline-offset-4">{{ __('ui::file.upload_now') }}</span>
                </p>
                @isset($hint)
                    <p class="text-[10px] font-bold uppercase tracking-widest text-base-content/40">{{ $hint }}</p>
                @endisset
            </div>
        </div>

        {{-- Preview State Content --}}
        <div x-show="files.length > 0" class="flex flex-col items-center gap-4" x-cloak>
            <div class="relative group">
                <img :src="files[0]?.url" class="h-32 w-32 rounded-xl object-cover shadow-xl border border-base-200 ring-4 ring-base-100" />
                <button 
                    type="button" 
                    x-on:click.stop.prevent="removeFile" 
                    class="btn btn-error btn-circle btn-xs absolute -right-2 -top-2 shadow-lg hover:scale-110 transition-transform"
                >
                    <x-tabler-x class="size-3" />
                </button>
            </div>
            <div class="text-center">
                <p class="max-w-[200px] truncate text-xs font-bold text-base-content/60" x-text="files[0]?.name"></p>
                <p class="mt-1 text-[10px] font-bold uppercase tracking-widest text-accent">{{ __('ui::file.change_file') }}</p>
            </div>
        </div>

        {{-- Drop Overlay Indicator --}}
        <div 
            x-show="isDropping" 
            x-transition.opacity
            class="absolute inset-0 z-30 flex flex-col items-center justify-center rounded-2xl bg-base-100/80 backdrop-blur-md pointer-events-none border-2 border-accent"
        >
            <div class="flex flex-col items-center gap-4">
                <div class="flex size-20 items-center justify-center rounded-full bg-accent/20 text-accent">
                    <x-tabler-upload class="size-10 animate-bounce" />
                </div>
                <div class="text-center">
                    <p class="text-lg font-black tracking-tight text-accent">{{ __('ui::file.release_to_upload') }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-accent/60">{{ __('ui::file.release_to_upload_hint') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Native Teleported Cropper Modal --}}
    <template x-teleport="body">
        <div
            x-show="showCropper"
            class="fixed inset-0 z-[10000] overflow-y-auto"
            style="display: none;"
            x-cloak
        >
            {{-- Backdrop --}}
            <div 
                x-show="showCropper"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-base-300/60 backdrop-blur-md" 
                @click="closeCropper()"
            ></div>

            {{-- Modal Container --}}
            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div 
                    x-show="showCropper"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95 translate-y-8 sm:translate-y-0"
                    x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-200"
                    x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                    x-transition:leave-end="opacity-0 scale-95 translate-y-8 sm:translate-y-0"
                    class="relative w-full max-w-2xl transform overflow-hidden rounded-[2.5rem] bg-base-100 p-6 text-left align-middle shadow-2xl transition-all border border-base-200 lg:p-10"
                    @click.stop
                >
                    {{-- Header --}}
                    <div class="mb-8">
                        <div class="flex items-start justify-between gap-6">
                            <div class="flex-1">
                                <h3 class="text-3xl font-black tracking-tight text-base-content">{{ __('ui::file.cropper.title') }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-base-content/60">{{ __('ui::file.cropper.subtitle') }}</p>
                            </div>
                            <button 
                                @click="closeCropper()" 
                                class="btn btn-ghost btn-circle btn-sm -mt-2 -mr-2 bg-base-200 hover:bg-error/10 hover:text-error"
                            >
                                <x-tabler-x class="size-6 opacity-40" />
                            </button>
                        </div>
                        <div class="divider my-6 opacity-10"></div>
                    </div>

                    {{-- Cropping Area --}}
                    <div class="relative min-h-[450px] w-full overflow-hidden rounded-3xl border border-base-200 bg-base-300 shadow-inner">
                        <img x-ref="cropperImage" class="block max-w-full" style="display: block; max-width: 100%;" />
                    </div>

                    {{-- Actions --}}
                    <div class="mt-10 flex flex-col items-center justify-between gap-4 sm:flex-row">
                        <div class="flex gap-2">
                            <button type="button" x-on:click="rotate(-90)" class="btn btn-ghost btn-circle bg-base-200" title="{{ __('ui::file.cropper.rotate_left') }}">
                                <x-tabler-rotate-2 class="size-5 opacity-60" />
                            </button>
                            <button type="button" x-on:click="rotate(90)" class="btn btn-ghost btn-circle bg-base-200" title="{{ __('ui::file.cropper.rotate_right') }}">
                                <x-tabler-rotate-clockwise-2 class="size-5 opacity-60" />
                            </button>
                        </div>
                        
                        <div class="flex w-full gap-3 sm:w-auto">
                            <button type="button" x-on:click="closeCropper()" class="btn btn-ghost flex-1 font-bold text-base-content/60 sm:flex-none sm:px-10">
                                {{ __('ui::file.cropper.cancel') }}
                            </button>
                            <button type="button" x-on:click="applyCrop()" class="btn btn-primary flex-1 px-12 font-bold shadow-xl shadow-primary/20 sm:flex-none">
                                {{ __('ui::file.cropper.save') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>

    @error($name)
        <p class="mt-2 text-xs font-bold text-error flex items-center gap-1 px-1">
            <x-tabler-alert-circle class="size-4" />
            {{ $message }}
        </p>
    @enderror
</div>
