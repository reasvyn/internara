<div>
    <x-slot:title>{{ __('document.reports') }}</x-slot:title>

    <x-core::ui.page-header :title="__('document.reports')" />

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($types as $key => $label)
            <x-mary-card class="transition-shadow hover:shadow-lg">
                <h3 class="mb-2 text-sm font-bold">{{ $label }}</h3>
                <p class="text-base-content/60 mb-4 text-xs">
                    {{ __('document.generate_desc', ['type' => strtolower($label)]) }}
                </p>
                <x-mary-button
                    wire:click="generateReport('{{ $key }}')"
                    :label="__('document.generate')"
                    icon="o-document-plus"
                    class="btn-primary btn-sm"
                />
            </x-mary-card>
        @endforeach
    </div>

    <x-mary-card>
        <h3 class="mb-4 text-sm font-bold">{{ __('document.generated_reports') }}</h3>

        <x-mary-table
            :headers="[['key' => 'name', 'label' => __('document.name')], ['key' => 'created_at', 'label' => __('document.generated')]]"
            :rows="$reports"
        >
            @scope('actions', $report)
                <div class="flex gap-2">
                    <a href="{{ route('sysadmin.reports.download', $report->id) }}" class="btn btn-sm btn-primary">
                        <x-mary-icon name="o-arrow-down-tray" class="size-4" />
                        {{ __('document.download') }}
                    </a>
                    <x-mary-button
                        aria-label="{{ __('common.actions.delete') }}"
                        wire:click="deleteReport('{{ $report->id }}')"
                        icon="o-trash"
                        class="btn-sm btn-ghost text-error"
                    />
                </div>
            @endscope
        </x-mary-table>
    </x-mary-card>
</div>
