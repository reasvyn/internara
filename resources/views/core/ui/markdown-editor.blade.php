@props([
    'label',
    'model',
    'rows' => 6,
    'hint' => null,
])

<div
    x-data="{
        tab: 'write',
        preview: '',
        renderPreview() {
            if (! window.marked) return;
            this.preview = window.marked.parse(this.$wire.get('{{ $model }}') || '');
        }
    }"
    x-init="
        $watch('tab', (value) => {
            if (value === 'preview') renderPreview();
        })
    "
    class="space-y-2"
>
    <label class="text-sm font-medium">{{ $label }}</label>

    <div class="border-base-content/10 mb-2 flex gap-4 border-b">
        <button
            type="button"
            @click="tab = 'write'"
            :class="tab === 'write'
                ? 'border-b-2 border-primary text-primary font-medium'
                : 'text-base-content/50 hover:text-base-content'"
            class="pb-2 text-sm transition-colors"
        >
            {{ __('common.write') }}
        </button>
        <button
            type="button"
            @click="tab = 'preview'"
            :class="tab === 'preview'
                ? 'border-b-2 border-primary text-primary font-medium'
                : 'text-base-content/50 hover:text-base-content'"
            class="pb-2 text-sm transition-colors"
        >
            {{ __('common.preview') }}
        </button>
    </div>

    <div x-show="tab === 'write'">
        <textarea
            wire:model="{{ $model }}"
            rows="{{ $rows }}"
            class="textarea border-base-content/10 bg-base-100 focus:border-primary/30 w-full resize-y rounded-xl border p-4 text-sm transition-colors focus:outline-none"
            placeholder="{{ __('common.write_your_announcement_in_markdown') }}"
        ></textarea>
    </div>

    <div
        x-show="tab === 'preview'"
        class="border-base-content/10 bg-base-100 prose prose-sm min-h-[200px] max-w-none rounded-xl border p-4 text-sm"
    >
        <div x-html="preview" class="prose prose-sm max-w-none"></div>
    </div>

    @if ($hint)
        <p class="text-base-content/60 text-xs">{{ $hint }}</p>
    @endif
</div>
