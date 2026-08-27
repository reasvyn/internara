<div>
    <x-slot:title>{{ __('document.reports') }}</x-slot:title>

    <x-ui::ui.page-header :title="__('document.reports')" />

    <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-2">
        @foreach ($types as $key => $label)
            <x-ts-card class="transition-shadow hover:shadow-lg">
                <h3 class="mb-2 text-sm font-bold">{{ $label }}</h3>
                <p class="text-base-content/60 mb-4 text-xs">
                    {{ __('document.generate_desc', ['type' => strtolower($label)]) }}
                </p>
                <x-ts-button
                    wire:click="generateReport('{{ $key }}')"
                    :text="__('document.generate')"
                    icon="document-plus"
                    color="primary"
                    sm
                />

        @endforeach
    </div>

    <x-ts-card shadowless>
        <h3 class="mb-4 text-sm font-bold">{{ __('document.generated_reports') }}</h3>

        <x-ts-table
            :headers="[['index' => 'name', 'label' => __('document.name')], ['index' => 'created_at', 'label' => __('document.generated')]]"
            :rows="$reports"
        >
            @interact('column_action', $report)
                <div class="flex gap-2">
                    <a href="{{ route('sysadmin.reports.download', $report->id) }}" class="btn btn-sm btn-primary">
                        <x-ts-icon name="arrow-down-tray" class="size-4" />
                        {{ __('document.download') }}
                    </a>
                    <x-ts-button
                        aria-label="{{ __('common.actions.delete') }}"
                        wire:click="deleteReport('{{ $report->id }}')"
                        icon="trash"
                        class="btn-sm btn-ghost text-error"
                    />
                </div>
            @endinteract
        </x-ts-table>
</div>
