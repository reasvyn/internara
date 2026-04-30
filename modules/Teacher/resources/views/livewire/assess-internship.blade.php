<div>
    <x-ui::header 
        :title="__('teacher::ui.dashboard.assess_student')" 
        :subtitle="$registration->student->name . ' - ' . $registration->placement->company_name" 
        :context="'teacher::ui.dashboard.title'"
    >
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" link="{{ route('teacher.dashboard') }}" variant="secondary" />
        </x-slot:actions>
    </x-ui::header>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-ui::card class="bg-base-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <x-ui::icon name="tabler.calendar-check" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <div class="text-xs opacity-70">{{ __('assessment::ui.evaluation.attendance') }}</div>
                        <div class="text-xl font-bold">{{ $complianceMetrics['attendance_score'] }}%</div>
                        <div class="text-[10px]">{{ $complianceMetrics['attended_days'] }} / {{ $complianceMetrics['total_days'] }} {{ __('assessment::ui.evaluation.days') }}</div>
                    </div>
                </div>
            </x-ui::card>
            <x-ui::card class="bg-base-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-secondary/10 rounded-lg">
                        <x-ui::icon name="tabler.book" class="w-8 h-8 text-secondary" />
                    </div>
                    <div>
                        <div class="text-xs opacity-70">{{ __('assessment::ui.evaluation.journal_completion') }}</div>
                        <div class="text-xl font-bold">{{ $complianceMetrics['journal_score'] }}%</div>
                        <div class="text-[10px]">{{ $complianceMetrics['approved_journals'] }} / {{ $complianceMetrics['total_days'] }} {{ __('assessment::ui.evaluation.entries') }}</div>
                    </div>
                </div>
            </x-ui::card>
            <x-ui::card class="bg-base-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-accent/10 rounded-lg">
                        <x-ui::icon name="tabler.chart-pie" class="w-8 h-8 text-accent" />
                    </div>
                    <div>
                        <div class="text-xs opacity-70">{{ __('assessment::ui.evaluation.compliance_score') }}</div>
                        <div class="text-xl font-bold">{{ $complianceMetrics['final_score'] }}%</div>
                        <div class="text-[10px]">{{ __('assessment::ui.evaluation.participation_weight', ['weight' => 50]) }}</div>
                    </div>
                </div>
            </x-ui::card>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
            <x-ui::card :title="__('teacher::ui.dashboard.evaluation')" shadow separator>
                <x-ui::form wire:submit="save">
                    <div class="grid grid-cols-1 gap-4">
                        @foreach($criteria as $key => $value)
                            <x-ui::input 
                                type="number" 
                                min="0" 
                                max="100" 
                                :label="ucfirst(str_replace('_', ' ', $key))" 
                                wire:model="criteria.{{ $key }}" 
                            />
                        @endforeach
                    </div>

                    <x-ui::textarea 
                        :label="__('assessment::ui.evaluation.feedback_notes')" 
                        wire:model="feedback" 
                        rows="4" 
                        class="mt-4"
                        :placeholder="__('teacher::ui.dashboard.placeholder_notes')"
                    />

                    <x-slot:actions>
                        <x-ui::button :label="__('ui::common.cancel')" link="{{ route('teacher.dashboard') }}" variant="secondary" />
                        <x-ui::button type="submit" :label="__('teacher::ui.dashboard.submit_evaluation')" variant="primary" spinner="save" />
                    </x-slot:actions>
                </x-ui::form>
            </x-ui::card>

            <x-ui::card :title="__('teacher::ui.dashboard.competency_recap')" :subtitle="__('teacher::ui.dashboard.competency_recap_subtitle')" shadow separator>
                @if($claimedCompetencies->isEmpty())
                    <div class="text-center py-8 opacity-50">
                        <x-ui::icon name="tabler.info-circle" class="w-12 h-12 mx-auto mb-2" />
                        <p>{{ __('assessment::ui.evaluation.no_competencies') }}</p>
                    </div>
                @else
                    <x-ui::table :headers="[
                        ['key' => 'name', 'label' => __('assessment::ui.evaluation.skill')],
                        ['key' => 'claimed_date', 'label' => __('assessment::ui.evaluation.date')],
                    ]" :rows="$claimedCompetencies">
                        @scope('cell_claimed_date', $competency)
                            {{ \Illuminate\Support\Carbon::parse($competency->claimed_date)->format('d M Y') }}
                        @endscope
                    </x-ui::table>
                @endif
            </x-ui::card>
        </div>
    </div>
</div>
