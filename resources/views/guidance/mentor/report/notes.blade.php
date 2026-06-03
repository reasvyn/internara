<x-core::ui.page-header :title="__('report.supervisor_notes_title')" :subtitle="__('report.supervisor_notes_subtitle')" />

<div class="max-w-2xl mx-auto mt-6">
    @if(!$reportId)
        <x-mary-card>
            <div class="p-6 text-center">
                <x-mary-icon name="o-information-circle" class="text-base-content/40 w-12 h-12 mx-auto mb-3" />
                <p class="text-base-content/60">{{ __('report.no_mentored_students') }}</p>
            </div>
        </x-mary-card>
    @else
        <x-mary-card>
            <div class="mb-4 p-3 bg-base-200 rounded-lg text-sm">
                <p class="text-base-content/60">{{ __('report.supervisor_notes_info') }}</p>
            </div>

            <x-mary-form wire:submit="save">
                <x-mary-textarea
                    :label="__('report.supervisor_notes')"
                    wire:model="notes"
                    :placeholder="__('report.supervisor_notes_placeholder')"
                    rows="6" />
                <x-slot:actions>
                    <x-mary-button :label="__('report.save_notes')" class="btn-primary" type="submit" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-card>
    @endif
</div>
