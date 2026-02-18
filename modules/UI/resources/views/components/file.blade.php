@props([
    'label' => null,
    'name' => 'file',
    'id' => null,
    'accept' => '*',
    'preview' => [],
    'multiple' => false,
    'hint' => null,
    'ratio' => '1/1', // Default to square
])

@php
    $id = $id ?? $name;
    $isMultiple = filter_var($multiple, FILTER_VALIDATE_BOOLEAN);
    $preview_array = array_filter(is_array($preview) ? $preview : [$preview]);

    // Construct crop config based on ratio
    $cropConfig = $attributes->get('crop-config', []);
    if ($ratio) {
        $cropConfig['aspectRatio'] = $ratio === 'square' ? 1 : (str_contains($ratio, '/') ? eval("return $ratio;") : $ratio);
    }

    // Extract wire:model and other mary-specific attributes
    $maryAttributes = $attributes->only(['wire:model', 'wire:model.live', 'wire:model.blur', 'crop-after-change']);
    $otherAttributes = $attributes->except(['wire:model', 'wire:model.live', 'wire:model.blur', 'crop-after-change', 'crop-config']);
@endphp

<div
    x-data="{
        isDropping: false,
        files: [],
        isMultiple: {{ $isMultiple ? 'true' : 'false' }},
        
        init() {
            this.loadInitialPreviews();
        },

        loadInitialPreviews() {
            const initial = {{ json_encode($preview_array) }};
            this.files = initial.map(url => ({
                id: 'old-' + Math.random().toString(36).substring(2, 9),
                url: url,
                name: url.split('/').pop().split('?')[0] || 'Existing File',
                size: null,
                isNew: false,
                file: null,
                extension: (url.split('.').pop() || '').toLowerCase()
            }));
        },

        getFileInput() {
            return this.$refs.maryFile.querySelector('input[type=file]');
        },

        handleFileChange(event) {
            this.syncFiles(event.target.files);
        },

        handleDrop(event) {
            this.isDropping = false;
            if (event.dataTransfer.files.length > 0) {
                const input = this.getFileInput();
                input.files = event.dataTransfer.files;
                input.dispatchEvent(new Event('change', { bubbles: true }));
                this.syncFiles(event.dataTransfer.files);
            }
        },

        syncFiles(fileList) {
            const newFiles = Array.from(fileList).map(file => ({
                id: 'new-' + Math.random().toString(36).substring(2, 9),
                url: URL.createObjectURL(file),
                name: file.name,
                size: file.size,
                isNew: true,
                file: file,
                extension: file.name.split('.').pop().toLowerCase()
            }));

            if (this.isMultiple) {
                this.files = [...this.files, ...newFiles];
            } else {
                this.files.forEach(f => { if(f.isNew) URL.revokeObjectURL(f.url) });
                this.files = newFiles;
            }
        },

        removeFile(id) {
            const file = this.files.find(f => f.id === id);
            if (file && file.isNew) URL.revokeObjectURL(file.url);
            
            this.files = this.files.filter(f => f.id !== id);
            
            if (file && file.isNew) {
                const input = this.getFileInput();
                if (!this.isMultiple) {
                    input.value = '';
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                } else {
                    this.rebuildInputFiles();
                }
            }
        },

        rebuildInputFiles() {
            const input = this.getFileInput();
            const dataTransfer = new DataTransfer();
            this.files.filter(f => f.isNew).forEach(f => dataTransfer.items.add(f.file));
            input.files = dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },

        formatBytes(bytes) {
            if (!bytes) return '';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        isImage(file) {
            if (!file) return false;
            const ext = file.extension || '';
            return ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'].includes(ext.toLowerCase());
        }
    }"
    {{ $otherAttributes->merge(['class' => 'w-full']) }}
>
    @isset($label)
        <label class="label mb-1 px-1">
            <span class="label-text font-semibold text-base-content/80">{{ $label }}</span>
        </label>
    @endisset

    {{-- Hidden Mary File Engine --}}
    <div class="hidden" x-ref="maryFile">
        <x-mary-file 
            id="{{ $id }}"
            name="{{ $name . ($isMultiple ? '[]' : '') }}"
            accept="{{ $accept }}"
            :multiple="$isMultiple"
            :crop-config="$cropConfig"
            x-on:change="handleFileChange($event)"
            {{ $maryAttributes }}
        />
    </div>

    @if($slot->isNotEmpty())
        {{-- Custom Trigger Mode (e.g., Avatar) --}}
        <div 
            class="cursor-pointer transition-all active:scale-95"
            x-on:click="getFileInput().click()"
            x-on:dragover.prevent="isDropping = true"
            x-on:dragleave.prevent="isDropping = false"
            x-on:drop.prevent="handleDrop($event)"
        >
            {{ $slot }}
        </div>
    @else
        {{-- Standard Dropzone Mode --}}
        <div class="h-full min-h-24 w-full rounded-2xl border border-base-200 bg-base-100 p-4 shadow-sm transition-all">
            <div
                class="flex h-full w-full cursor-pointer items-center justify-center rounded-xl border-2 border-dashed border-base-200 p-4 hover:border-accent/50 hover:bg-accent/5 transition-colors group/dropzone relative"
                x-on:dragover.prevent="isDropping = true"
                x-on:dragleave.prevent="isDropping = false"
                x-on:drop.prevent="handleDrop($event)"
                x-bind:class="{ 'border-accent bg-accent/10': isDropping }"
                x-on:click="getFileInput().click()"
            >
                <div class="text-center text-xs text-base-content/60 relative w-full pointer-events-none">
                    {{-- Drag-over Overlay --}}
                    <div
                        x-show="isDropping"
                        class="absolute inset-0 z-10 flex size-full flex-col items-center justify-center gap-2 overflow-hidden rounded-xl bg-base-100/90 p-4 text-center font-medium"
                    >
                        <x-ui::icon name="tabler.upload" class="size-12 text-accent animate-bounce" />
                        <p class="text-accent text-sm">{{ $isMultiple ? __('ui::file.drop_to_add') : __('ui::file.drop_to_replace') }}</p>
                    </div>

                    {{-- Previews --}}
                    <div x-show="files.length > 0" class="w-full">
                        @if($isMultiple)
                            <ul class="w-full space-y-3 p-2 text-left pointer-events-auto">
                                <template x-for="fileItem in files" :key="fileItem.id">
                                    <li class="flex items-center gap-3 p-3 bg-base-200/50 rounded-xl border border-base-200">
                                        <template x-if="isImage(fileItem) && fileItem.url">
                                            <img x-bind:src="fileItem.url" class="size-10 flex-shrink-0 rounded-lg object-cover shadow-sm" />
                                        </template>
                                        <template x-if="!isImage(fileItem)">
                                            <div class="size-10 flex items-center justify-center bg-base-300 rounded-lg flex-shrink-0">
                                                <x-ui::icon name="tabler.file" class="size-6 text-base-content/40" />
                                            </div>
                                        </template>
                                        <div class="flex-grow overflow-hidden text-left">
                                            <p class="truncate text-sm font-bold text-base-content" x-text="fileItem.name"></p>
                                            <p class="text-[10px] uppercase tracking-wider text-base-content/50 font-semibold">
                                                <span x-text="formatBytes(fileItem.size)"></span>
                                                <span class="ml-1" x-text="fileItem.extension"></span>
                                            </p>
                                        </div>
                                        <button type="button" x-on:click.stop="removeFile(fileItem.id)" class="btn btn-ghost btn-circle btn-sm hover:bg-error/10 hover:text-error transition-colors flex-shrink-0">
                                            <x-ui::icon name="tabler.trash" class="size-4" />
                                        </button>
                                    </li>
                                </template>
                            </ul>
                        @else
                            <div class="flex items-center justify-center py-4 pointer-events-auto">
                                <div class="relative group/preview">
                                    <template x-if="isImage(files[0])">
                                        <img x-bind:src="files[0]?.url" class="h-40 w-40 rounded-2xl object-cover shadow-lg border border-base-200" />
                                    </template>
                                    <template x-if="!isImage(files[0])">
                                        <div class="flex h-40 w-40 flex-col items-center justify-center rounded-2xl bg-base-200 border border-base-300">
                                            <x-ui::icon name="tabler.file-text" class="size-16 text-base-content/20" />
                                            <p class="mt-2 w-full truncate px-4 text-center text-xs font-bold text-base-content/60" x-text="files[0]?.name"></p>
                                        </div>
                                    </template>
                                    <button type="button" x-on:click.stop="removeFile(files[0].id)" class="btn btn-error btn-circle btn-sm absolute -right-3 -top-3 z-10 shadow-lg opacity-0 group-hover/preview:opacity-100 transition-opacity">
                                        <x-ui::icon name="tabler.x" class="size-4" />
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Empty State --}}
                    <div x-show="files.length === 0" class="flex flex-col items-center justify-center text-center gap-3 py-8">
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
                    </div>
                </div>
            </div>
        </div>
    @endif

    @error($name)
        <p class="mt-2 text-sm text-error font-medium px-1 flex items-center gap-1">
            <x-ui::icon name="tabler.alert-circle" class="size-4" />
            {{ $message }}
        </p>
    @enderror
</div>
