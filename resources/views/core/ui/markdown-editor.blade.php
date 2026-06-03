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
            if (!window.marked) return;
            this.preview = window.marked.parse(this.$wire.get('{{ $model }}') || '');
        }
    }"
    x-init="$watch('tab', value => { if (value === 'preview') renderPreview(); })"
    class="space-y-2"
>
    <label class="font-medium text-sm">{{ $label }}</label>

    <div class="flex gap-4 border-b border-base-content/10 mb-2">
        <button type="button"
            @click="tab = 'write'"
            :class="tab === 'write' ? 'border-b-2 border-primary text-primary font-medium' : 'text-base-content/50 hover:text-base-content'"
            class="pb-2 text-sm transition-colors"
        >{{ __('common.write') }}</button>
        <button type="button"
            @click="tab = 'preview'"
            :class="tab === 'preview' ? 'border-b-2 border-primary text-primary font-medium' : 'text-base-content/50 hover:text-base-content'"
            class="pb-2 text-sm transition-colors"
        >{{ __('common.preview') }}</button>
    </div>

    <div x-show="tab === 'write'">
        <textarea
            wire:model="{{ $model }}"
            rows="{{ $rows }}"
            class="textarea w-full border border-base-content/10 rounded-xl bg-base-100 p-4 text-sm focus:border-primary/30 focus:outline-none transition-colors resize-y"
            placeholder="{{ __('common.write_your_announcement_in_markdown') }}"
        ></textarea>
    </div>

    <div x-show="tab === 'preview'" class="min-h-[200px] border border-base-content/10 rounded-xl bg-base-100 p-4 text-sm prose prose-sm max-w-none">
        <div x-html="preview" class="prose prose-sm max-w-none"></div>
    </div>

    @if($hint)
        <p class="text-xs text-base-content/40">{{ $hint }}</p>
    @endif
</div>
