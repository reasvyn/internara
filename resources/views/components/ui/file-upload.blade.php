@props([
    'label' => null,
    'hint' => null,
    'errorKey' => null,
    'multiple' => false,
    'accept' => '*/*',
    'maxFiles' => 10,
    'maxSize' => 10240,
    'isCrop' => false,
    'ratio' => '1/1',
    'wireModel' => null,
    'preview' => null,
    'icon' => 'o-photo',
])

@php
    $uuid = uniqid('file-', true);
    $errorKey = $errorKey ?? ($wireModel ? str_replace(['[', ']'], ['.', ''], $wireModel) : '');
@endphp

<div wire:key="{{ $uuid }}" {{ $attributes->merge(['class' => 'mb-4']) }} x-data="fileUpload({{ json_encode([
    'model' => $wireModel,
    'multiple' => $multiple,
    'isCrop' => $isCrop,
    'ratio' => $ratio,
    'accept' => $accept,
    'maxFiles' => $maxFiles,
    'maxSize' => $maxSize,
    'preview' => $preview,
]) }})">
    {{-- Label --}}
    @if($label)
        <label class="block text-sm font-medium text-base-content mb-2">
            {{ $label }}
            @if(!$multiple)
                <span class="text-base-content/40 font-normal">(optional)</span>
            @endif
        </label>
    @endif

    {{-- Drop Zone --}}
    <div
        class="relative border-2 border-dashed rounded-xl p-8 text-center transition-all cursor-pointer
               {{ $multiple ? 'hover:border-primary/30' : 'hover:border-primary/30' }}
               bg-base-200/30"
        :class="{ 'border-primary bg-primary/5': isDropping }"
        @dragover.prevent="isDropping = true"
        @dragleave.prevent="isDropping = false"
        @drop.prevent="handleDrop($event)"
        @click="$refs.input.click()"
    >
        <input
            x-ref="input"
            type="file"
            @if($accept) accept="{{ $accept }}" @endif
            @change="handleSelect($event)"
            @if($multiple) multiple @endif
            class="hidden"
        />

        {{-- Upload Prompt (shown when no files) --}}
        <div x-show="files.length === 0" class="flex flex-col items-center gap-2">
            <x-mary-icon name="o-cloud-arrow-up" class="size-8 text-base-content/30" />
            <p class="text-sm font-medium text-base-content/50">
                {{ $multiple ? 'Drag & drop files here or click to browse' : 'Drag & drop a file here or click to browse' }}
            </p>
            @if($multiple)
                <p class="text-xs text-base-content/30">
                    Up to {{ $maxFiles }} files, max {{ number_format($maxSize) }} KB each
                </p>
            @else
                <p class="text-xs text-base-content/30">Max {{ number_format($maxSize) }} KB</p>
            @endif
        </div>

        {{-- File Preview Grid (shown when files exist) --}}
        <div x-show="files.length > 0" class="space-y-3">
            <template x-for="(file, index) in files" :key="file.id">
                <div class="flex items-center gap-3 p-3 bg-base-100 rounded-lg border border-base-content/5">
                    {{-- Image Thumbnail --}}
                    <template x-if="file.type.startsWith('image/')">
                        <img :src="file.url" class="size-10 rounded-lg object-cover flex-shrink-0" />
                    </template>

                    {{-- Generic File Icon --}}
                    <template x-if="!file.type.startsWith('image/')">
                        <div class="size-10 rounded-lg bg-primary/10 text-primary flex items-center justify-center flex-shrink-0">
                            <x-mary-icon name="o-document" class="size-5" />
                        </div>
                    </template>

                    {{-- File Info --}}
                    <div class="flex-1 min-w-0 text-left">
                        <p class="text-sm font-medium truncate" x-text="file.name"></p>
                        <p class="text-xs text-base-content/40" x-text="formatSize(file.size)"></p>
                    </div>

                    {{-- Actions --}}
                    <div class="flex items-center gap-1 flex-shrink-0">
                        {{-- Crop Button (images only) --}}
                        <template x-if="file.type.startsWith('image/') && isCrop && file.isNew">
                            <button
                                type="button"
                                @click.stop="editFile(index)"
                                class="p-1.5 rounded-lg text-base-content/40 hover:text-primary hover:bg-primary/10 transition-colors"
                                title="Crop image"
                            >
                                <x-mary-icon name="o-scissors" class="size-4" />
                            </button>
                        </template>

                        {{-- Remove Button --}}
                        <button
                            type="button"
                            @click.stop="removeFile(index)"
                            class="p-1.5 rounded-lg text-base-content/40 hover:text-error hover:bg-error/10 transition-colors"
                            title="Remove file"
                        >
                            <x-mary-icon name="o-x-mark" class="size-4" />
                        </button>
                    </div>
                </div>
            </template>

            {{-- Add More (multiple mode) --}}
            <template x-if="multiple && files.length < maxFiles">
                <button
                    type="button"
                    @click.stop="$refs.input.click()"
                    class="w-full flex items-center justify-center gap-2 p-3 border border-dashed border-base-content/20 rounded-lg text-sm text-base-content/40 hover:text-primary hover:border-primary/30 transition-colors"
                >
                    <x-mary-icon name="o-plus" class="size-4" />
                    Add another file
                </button>
            </template>
        </div>
    </div>

    {{-- Error --}}
    @if($errorKey)
        <p class="mt-1 text-xs text-error" x-show="errorMsg" x-text="errorMsg"></p>
        @error($errorKey)
            <p class="mt-1 text-xs text-error">{{ $message }}</p>
        @enderror
    @endif

    {{-- Hint --}}
    @if($hint)
        <p class="mt-1 text-xs text-base-content/40">{{ $hint }}</p>
    @endif

    {{-- Cropper Modal --}}
    <div x-show="showCropper" x-cloak class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/70 p-4" @keydown.escape.window="closeCropper()">
        <div class="bg-base-100 rounded-2xl shadow-2xl w-full max-w-3xl lg:max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-base-content/5">
                <h3 class="text-sm font-semibold">Crop Image</h3>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-base-content/50" x-text="'Ratio: ' + ratio"></span>
                    <button type="button" @click="closeCropper()" class="p-1.5 rounded-lg hover:bg-base-200 transition-colors">
                        <x-mary-icon name="o-x-mark" class="size-4" />
                    </button>
                </div>
            </div>

            {{-- Cropper Canvas Area --}}
            <div x-ref="cropperContainer" class="relative bg-black overflow-hidden" style="height: 60vh; min-height: 400px;">
                <img x-ref="cropperImage" class="block" />
                {{-- Fixed Ratio Overlay --}}
                <template x-if="isCrop && ratio">
                    <div
                        class="absolute border-2 border-white shadow-[0_0_0_9999px_rgba(0,0,0,0.6)] cursor-move touch-none select-none"
                        :style="cropOverlayStyle"
                        @mousedown.prevent="startDrag($event)"
                        @touchstart.prevent="startDrag($event)"
                    >
                        {{-- Grid Lines (Rule of Thirds) --}}
                        <div class="absolute inset-0 pointer-events-none">
                            <div class="absolute inset-0 border border-white/20"></div>
                            <div class="absolute left-1/3 top-0 bottom-0 border-l border-white/20"></div>
                            <div class="absolute left-2/3 top-0 bottom-0 border-l border-white/20"></div>
                            <div class="absolute top-1/3 left-0 right-0 border-t border-white/20"></div>
                            <div class="absolute top-2/3 left-0 right-0 border-t border-white/20"></div>
                        </div>

                        {{-- Resize Handles --}}
                        <div class="absolute -top-1.5 -left-1.5 w-4 h-4 bg-white rounded-full cursor-nwse-resize border border-black/20" @mousedown.prevent="startResize($event, 'nw')" @touchstart.prevent="startResize($event, 'nw')"></div>
                        <div class="absolute -top-1.5 -right-1.5 w-4 h-4 bg-white rounded-full cursor-nesw-resize border border-black/20" @mousedown.prevent="startResize($event, 'ne')" @touchstart.prevent="startResize($event, 'ne')"></div>
                        <div class="absolute -bottom-1.5 -left-1.5 w-4 h-4 bg-white rounded-full cursor-nesw-resize border border-black/20" @mousedown.prevent="startResize($event, 'sw')" @touchstart.prevent="startResize($event, 'sw')"></div>
                        <div class="absolute -bottom-1.5 -right-1.5 w-4 h-4 bg-white rounded-full cursor-nwse-resize border border-black/20" @mousedown.prevent="startResize($event, 'se')" @touchstart.prevent="startResize($event, 'se')"></div>
                    </div>
                </template>
            </div>

            {{-- Interactive Toolbar --}}
            <div class="flex items-center justify-between p-4 border-t border-base-content/5">
                <div class="flex items-center gap-1">
                    <button type="button" @click="zoomIn()" class="p-2 rounded-lg hover:bg-base-200 transition-colors" title="Zoom in">
                        <x-mary-icon name="o-magnifying-glass-plus" class="size-4" />
                    </button>
                    <button type="button" @click="zoomOut()" class="p-2 rounded-lg hover:bg-base-200 transition-colors" title="Zoom out">
                        <x-mary-icon name="o-magnifying-glass-minus" class="size-4" />
                    </button>
                    <div class="w-px h-6 bg-base-content/10 mx-1"></div>
                    <button type="button" @click="rotate(-90)" class="p-2 rounded-lg hover:bg-base-200 transition-colors" title="Rotate left">
                        <x-mary-icon name="o-arrow-uturn-left" class="size-4" />
                    </button>
                    <button type="button" @click="rotate(90)" class="p-2 rounded-lg hover:bg-base-200 transition-colors" title="Rotate right">
                        <x-mary-icon name="o-arrow-uturn-right" class="size-4" />
                    </button>
                    <div class="w-px h-6 bg-base-content/10 mx-1"></div>
                    <button type="button" @click="resetCropper()" class="p-2 rounded-lg hover:bg-base-200 transition-colors" title="Reset">
                        <x-mary-icon name="o-arrow-path" class="size-4" />
                    </button>
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="closeCropper()" class="btn btn-ghost btn-sm rounded-lg">Cancel</button>
                    <button type="button" @click="applyCrop()" class="btn btn-primary btn-sm rounded-lg">Apply Crop</button>
                </div>
            </div>
        </div>
    </div>
</div>
