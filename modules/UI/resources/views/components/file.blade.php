@props([
    'label' => null,
    'name' => 'file',
    'id' => null,
    'accept' => '*',
    'preview' => [],
    'multiple' => false,
    'hint' => null,
    'aos' => null,
])

@php
    $id = $id ?? $name;
    $isMultiple = filter_var($multiple, FILTER_VALIDATE_BOOLEAN);
    $preview_array = ! is_array($preview) ? (is_null($preview) ? [] : [$preview]) : $preview;

    // Extract wire:model and other mary-specific attributes
    $maryAttributes = $attributes->only(['wire:model', 'wire:model.live', 'wire:model.blur', 'crop-after-change', 'crop-config']);
    $otherAttributes = $attributes->except(['wire:model', 'wire:model.live', 'wire:model.blur', 'crop-after-change', 'crop-config']);
@endphp

<div
    x-data="{
        isDropping: false,
        files: [],
        isMultiple: {{ $isMultiple ? 'true' : 'false' }},
        _programmaticChange: false,

        init() {
            let initial = {{ json_encode($preview_array) }}
            this.files = initial.map((url) => {
                const name = url.substring(url.lastIndexOf('/') + 1)
                return {
                    id: this.generateId(),
                    url: url,
                    name: name,
                    size: null,
                    isNew: false,
                    file: null,
                    extension: this.getFileExtension(name),
                }
            })
        },

        addFiles(newFiles) {
            let addedFiles = Array.from(newFiles).map((file) => {
                return {
                    id: this.generateId(),
                    url: URL.createObjectURL(file),
                    name: file.name,
                    size: file.size,
                    isNew: true,
                    file: file,
                    extension: this.getFileExtension(file.name),
                }
            })

            if (this.isMultiple) {
                this.files = this.files.concat(addedFiles)
            } else {
                this.files
                    .filter((f) => f.isNew)
                    .forEach((f) => URL.revokeObjectURL(f.url))
                this.files = addedFiles
            }
            this.updateFileInput()
        },

        removeFile(idToRemove) {
            let fileToRemove = this.files.find((f) => f.id === idToRemove)
            if (fileToRemove && fileToRemove.isNew) {
                URL.revokeObjectURL(fileToRemove.url)
            }
            this.files = this.files.filter((f) => f.id !== idToRemove)
            this.updateFileInput()
        },

        updateFileInput() {
            let input = this.$refs.maryFile.querySelector('input[type=file]')
            if (!input) return

            let dataTransfer = new DataTransfer()
            this.files
                .filter((f) => f.isNew)
                .forEach((f) => {
                    dataTransfer.items.add(f.file)
                })
            input.files = dataTransfer.files

            this._programmaticChange = true
            input.dispatchEvent(new Event('change'))
            this.$nextTick(() => {
                this._programmaticChange = false
            })
        },

        handleFileChange(event) {
            if (this._programmaticChange) return
            this.addFiles(event.target.files)
        },

        handleDrop(event) {
            this.isDropping = false
            if (event.dataTransfer.files.length > 0) {
                this.addFiles(event.dataTransfer.files)
            }
        },

        generateId() {
            return Math.random().toString(36).substring(2, 9)
        },
        formatBytes(bytes, decimals = 2) {
            if (bytes === 0 || bytes === null) return 'N/A'
            const k = 1024
            const dm = decimals < 0 ? 0 : decimals
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
            const i = Math.floor(Math.log(bytes) / Math.log(k))
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i]
        },
        getFileExtension(filename) {
            return filename.split('.').pop() || ''
        },
        isImage(file) {
            if (file.isNew) {
                return file.file.type.startsWith('image/')
            }
            const ext = this.getFileExtension(file.url)
            return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext.toLowerCase())
        },

        get dropzoneOverlayText() {
            return this.isMultiple
                ? '{{ __("ui::file.drop_to_add") }}'
                : '{{ __("ui::file.drop_to_replace") }}'
        },
    }"
    {{ $otherAttributes->merge(['class' => 'mb-4']) }}
    data-aos="{{ $aos }}"
>
    @isset($label)
        <label for="{{ $id }}" class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endisset

    <div class="h-full min-h-24 w-full rounded-2xl border border-base-200 bg-base-100 p-4 shadow-sm transition-all">
        <div
            class="flex h-full w-full cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-base-200 p-4 hover:border-accent/50 hover:bg-accent/5 transition-colors"
            x-on:dragover.prevent="isDropping = true"
            x-on:dragleave.prevent="isDropping = false"
            x-on:drop.prevent="handleDrop($event)"
            x-bind:class="{ 'border-accent bg-accent/10': isDropping }"
            x-on:click.prevent="! isDropping && $refs.maryFile.querySelector('input[type=file]').click()"
        >
            {{-- Hidden Mary File Component acting as the engine --}}
            <div class="hidden" x-ref="maryFile">
                <x-mary-file
                    id="{{ $id }}"
                    name="{{ $name . ($isMultiple ? '[]' : '') }}"
                    accept="{{ $accept }}"
                    multiple="{{ $isMultiple }}"
                    x-on:change="handleFileChange($event)"
                    {{ $maryAttributes }}
                />
            </div>

            <div class="text-center text-xs text-base-content/60 relative w-full">
                {{-- Overlay for drag-over --}}
                <div
                    x-show="isDropping"
                    class="absolute inset-0 z-10 flex size-full flex-col items-center justify-center gap-2 overflow-hidden rounded-xl bg-base-100/90 p-4 text-center font-medium"
                >
                    <x-ui::icon name="tabler.upload" class="size-12 text-accent animate-bounce" />
                    <p class="text-accent text-sm" x-text="dropzoneOverlayText"></p>
                </div>

                {{-- File Previews --}}
                <template x-if="files.length > 0">
                    <div class="w-full">
                        <template x-if="isMultiple">
                            <ul class="w-full space-y-3 p-2 text-left">
                                <template x-for="fileItem in files" :key="fileItem.id">
                                    <li class="flex items-center gap-3 p-3 bg-base-200/50 rounded-xl border border-base-200">
                                        <template x-if="isImage(fileItem) && fileItem.url">
                                            <img x-bind:src="fileItem.url" class="size-10 flex-shrink-0 rounded-lg object-cover shadow-sm" />
                                        </template>
                                        <template x-if="! isImage(fileItem) || ! fileItem.url">
                                            <div class="size-10 flex items-center justify-center bg-base-300 rounded-lg flex-shrink-0">
                                                <x-ui::icon name="tabler.file" class="size-6 text-base-content/40" />
                                            </div>
                                        </template>
                                        <div class="flex-grow overflow-hidden text-left">
                                            <p class="truncate text-sm font-bold text-base-content" x-text="fileItem.name"></p>
                                            <p class="text-[10px] uppercase tracking-wider text-base-content/50 font-semibold">
                                                <span x-text="formatBytes(fileItem.size)"></span>
                                                <template x-if="fileItem.extension">
                                                    <span class="ml-1" x-text="fileItem.extension"></span>
                                                </template>
                                            </p>
                                        </div>
                                        <button type="button" x-on:click.stop="removeFile(fileItem.id)" class="btn btn-ghost btn-circle btn-sm hover:bg-error/10 hover:text-error transition-colors flex-shrink-0">
                                            <x-ui::icon name="tabler.trash" class="size-4" />
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </template>

                        <template x-if="!isMultiple">
                            <div class="flex items-center justify-center py-4">
                                <div class="relative group">
                                    <template x-if="isImage(files[0])">
                                        <img x-bind:src="files[0].url" class="h-40 w-40 rounded-2xl object-cover shadow-lg border border-base-200" />
                                    </template>
                                    <template x-if="! isImage(files[0])">
                                        <div class="flex h-40 w-40 flex-col items-center justify-center rounded-2xl bg-base-200 border border-base-300">
                                            <x-ui::icon name="tabler.file-text" class="size-16 text-base-content/20" />
                                            <p class="mt-2 w-full truncate px-4 text-center text-xs font-bold text-base-content/60" x-text="files[0].name"></p>
                                        </div>
                                    </template>
                                    <button type="button" x-on:click.stop="removeFile(files[0].id)" class="btn btn-error btn-circle btn-sm absolute -right-3 -top-3 z-10 shadow-lg opacity-0 group-hover:opacity-100 transition-opacity">
                                        <x-ui::icon name="tabler.x" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="files.length === 0">
                    <div class="flex flex-col items-center justify-center text-center gap-3 py-8 overflow-hidden">
                        @if($slot->isNotEmpty())
                            {{ $slot }}
                        @else
                            <div class="size-16 flex items-center justify-center bg-base-200 rounded-full mb-2">
                                <x-ui::icon name="tabler.cloud-upload" class="size-8 text-base-content/30" />
                            </div>
                            <p class="text-sm">
                                {{ __('ui::file.instruction') }} 
                                <span class="text-accent font-bold">{{ __('ui::file.click_to_upload') }}</span>
                            </p>
                            @isset($hint)
                                <p class="text-[11px] text-base-content/40 font-medium uppercase tracking-tight">{{ $hint }}</p>
                            @endisset
                        @endif
                    </div>
                </template>
            </div>
        </div>
    </div>

    @error($name)
        <p class="mt-2 text-sm text-error font-medium px-1 flex items-center gap-1">
            <x-ui::icon name="tabler.alert-circle" class="size-4" />
            {{ $message }}
        </p>
    @enderror
</div>
