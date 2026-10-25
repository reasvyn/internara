<x-ui::components.record-manager :title="__('document.reports')" :subtitle="__('document.generated_reports')">
    <x-slot:stats>
        @foreach ($types as $key => $label)
            <div class="bg-base-100 border-base-content/10 flex flex-col justify-between rounded-2xl border p-4 shadow-2xs">
                <div>
                    <h3 class="text-sm font-bold">{{ $label }}</h3>
                    <p class="text-base-content/60 mt-1 line-clamp-2 text-xs">
                        {{ __('document.generate_desc', ['type' => strtolower($label)]) }}
                    </p>
                </div>
                <div class="mt-3">
                    <x-ts-button
                        wire:click="generateReport('{{ $key }}')"
                        :text="__('document.generate')"
                        icon="document-plus"
                        color="primary"
                        xs
                        class="w-full"
                    />
                </div>
            </div>
        @endforeach
    </x-slot:stats>

    <x-ts-table :headers="$this->headers()" :rows="$reports" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_name', $report)
            <span class="text-sm font-medium">{{ $report->name }}</span>
        @endinteract

        @interact('column_created_at', $report)
            <span class="text-sm">{{ $report->created_at?->format('d M Y H:i') ?? '—' }}</span>
        @endinteract

        @interact('column_actions', $report)
            <x-ui::components.table-actions>
                <x-ui::components.table-action
                    action="download"
                    href="{{ $report->getFirstMediaUrl('file') }}"
                    target="_blank"
                    :label="__('document.download').' '.$report->name"
                />
                <x-ui::components.table-action
                    action="delete"
                    wire:click="askDeleteReport('{{ $report->id }}')"
                    :label="__('common.actions.delete').' '.$report->name"
                />
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>
</x-ui::components.record-manager>
