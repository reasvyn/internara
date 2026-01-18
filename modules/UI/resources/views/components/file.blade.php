@props([
    'label' => null,
    'name' => 'file',
    'id' => null,
    'accept' => '*',
    'preview' => [],
    'multiple' => false,
    'hint' => null,
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
                ? 'Jatuhkan berkas di sini untuk menambahkan.'
                : 'Jatuhkan berkas di sini untuk mengganti.'
        },
    }"
    {{ $otherAttributes->merge(['class' => 'mb-4']) }}
>
    @isset($label)
        <label for="{{ $id }}" class="text-xs font-bold">
            {{ $label }}
        </label>
    @endisset

    <div class="mt-2 h-full min-h-24 w-full rounded-lg border border-gray-300 p-4 dark:border-gray-300">
        <div
            class="border-3 flex h-full w-full cursor-pointer items-center justify-center rounded-lg border-dashed border-gray-300 p-4 hover:border-gray-500 dark:border-gray-700"
            x-on:dragover.prevent="isDropping = true"
            x-on:dragleave.prevent="isDropping = false"
            x-on:drop.prevent="handleDrop($event)"
            x-bind:class="{ 'border-primary': isDropping }"
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

            <div class="text-center text-xs text-gray-500 relative w-full">
                {{-- Overlay for drag-over --}}
                <div
                    x-show="isDropping"
                    class="absolute inset-0 z-10 flex size-full flex-col items-center justify-center gap-2 overflow-hidden rounded-lg bg-base-100/90 p-4 text-center font-medium dark:bg-base-900/90"
                >
                    <x-ui::icon name="tabler.upload" class="h-12 w-12 text-primary" />
                    <p class="text-primary" x-text="dropzoneOverlayText"></p>
                </div>

                {{-- File Previews --}}
                <template x-if="files.length > 0">
                    <div>
                        <template x-if="isMultiple">
                            <ul class="w-full space-y-2 p-2 text-left">
                                <template x-for="fileItem in files" :key="fileItem.id">
                                    <li class="flex items-center gap-2 p-2">
                                        <template x-if="isImage(fileItem) && fileItem.url">
                                            <img x-bind:src="fileItem.url" class="h-8 w-8 flex-shrink-0 rounded-md object-cover" />
                                        </template>
                                        <template x-if="! isImage(fileItem) || ! fileItem.url">
                                            <x-ui::icon name="tabler.file" class="h-8 w-8 flex-shrink-0 text-gray-400" />
                                        </template>
                                        <div class="flex-grow overflow-hidden text-left">
                                            <p class="break-all text-sm font-medium" x-text="fileItem.name"></p>
                                            <p class="text-xs text-gray-400">
                                                <span x-text="formatBytes(fileItem.size)"></span>
                                                <template x-if="fileItem.extension">
                                                    <span class="ml-1">(<span x-text="fileItem.extension"></span>)</span>
                                                </template>
                                            </p>
                                        </div>
                                        <button type="button" x-on:click.stop="removeFile(fileItem.id)" class="btn btn-ghost btn-circle btn-xs flex-shrink-0">
                                            <x-ui::icon name="tabler.x" class="h-4 w-4" />
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        </template>

                        <template x-if="!isMultiple">
                            <div class="flex items-center justify-center">
                                <div class="relative">
                                    <template x-if="isImage(files[0])">
                                        <img x-bind:src="files[0].url" class="h-32 w-32 rounded-lg object-cover" />
                                    </template>
                                    <template x-if="! isImage(files[0])">
                                        <div class="flex h-32 w-32 flex-col items-center justify-center rounded-lg bg-gray-100 p-2 dark:bg-gray-800">
                                            <x-ui::icon name="tabler.file-text" class="h-12 w-12 text-gray-400" />
                                            <p class="mt-2 w-full truncate px-1 text-center text-xs text-gray-500" x-text="files[0].name"></p>
                                        </div>
                                    </template>
                                    <button type="button" x-on:click.stop="removeFile(files[0].id)" class="btn btn-primary btn-circle btn-xs absolute -right-2 -top-2 z-10">
                                        <x-ui::icon name="tabler.x" class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                <template x-if="files.length === 0">
                    <div class="flex flex-col items-center justify-center text-center text-xs font-medium gap-2 size-full overflow-hidden">
                        @if($slot->isNotEmpty())
                            {{ $slot }}
                        @else
                            <x-ui::icon name="tabler.upload" class="mb-2 h-8 w-8 text-gray-400" />
                            <p>Jatuhkan berkas di sini atau <span class="text-primary font-semibold">klik untuk mengunggah.</span></p>
                            @isset($hint)
                                <p class="mt-1 text-gray-400 font-normal">{{ $hint }}</p>
                            @endisset
                        @endif
                    </div>
                </template>
            </div>
        </div>
    </div>

    @error($name)
        <p class="mt-1 text-xs text-error">{{ $message }}</p>
    @enderror
</div>
