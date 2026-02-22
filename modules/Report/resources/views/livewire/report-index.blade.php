<div>
    <x-ui::header 
        :title="__('report::ui.title')" 
        :subtitle="__('report::ui.subtitle')" 
        :context="'report::ui.title'"
    />

    <x-ui::card>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4">
                <x-ui::select 
                    :label="__('report::ui.select_report')" 
                    wire:model.live="selectedProvider" 
                    :options="$this->providers" 
                    :placeholder="__('report::ui.choose')"
                />

                @if($selectedProvider === 'internship_class_summary' || $selectedProvider === 'competency_achievement_summary')
                    <x-ui::input 
                        :label="__('report::ui.academic_year')" 
                        wire:model="filters.academic_year" 
                    />
                @endif

                @if($selectedProvider === 'partner_engagement_analytics')
                    <x-ui::select 
                        :label="__('report::ui.internship_program')" 
                        wire:model="filters.internship_id"
                        :options="$this->internships"
                        :placeholder="__('report::ui.choose_internship')"
                    />
                @endif

                <div class="flex items-end">
                    <x-ui::button 
                        :label="__('report::ui.generate')" 
                        variant="primary"
                        class="w-full" 
                        icon="tabler.file-export"
                        wire:click="generate" 
                        spinner="generate"
                        :disabled="!$selectedProvider"
                    />
                </div>
            </div>
        </x-ui::card>

        <div class="mt-8">
            <x-ui::card :title="__('report::ui.history')">
                @if($this->history->isEmpty())
                    <p class="text-sm opacity-50 italic text-center py-8">{{ __('report::ui.no_history') }}</p>
                @else
                    <x-ui::table :headers="[
                        ['key' => 'provider_identifier', 'label' => __('report::ui.report_type')],
                        ['key' => 'created_at', 'label' => __('report::ui.date')],
                        ['key' => 'actions', 'label' => '']
                    ]" :rows="$this->history" with-pagination>
                        @scope('cell_provider_identifier', $report)
                            <span class="font-bold">{{ $report->provider_identifier }}</span>
                        @endscope

                        @scope('cell_created_at', $report)
                            <span class="text-xs opacity-70">{{ $report->created_at->translatedFormat('d M Y H:i') }}</span>
                        @endscope

                        @scope('actions', $report)
                            <x-ui::button icon="tabler.download" variant="tertiary" class="btn-sm" link="{{ \Illuminate\Support\Facades\Storage::url($report->file_path) }}" external />
                        @endscope
                    </x-ui::table>
                @endif
            </x-ui::card>
        </div>
    </div>
</div>
