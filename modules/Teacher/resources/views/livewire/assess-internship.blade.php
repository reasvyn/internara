<div>
    <x-ui::header title="{{ __('Assess Student') }}" subtitle="{{ $registration->student->name }} - {{ $registration->placement->company_name }}" />

    <x-ui::main>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <x-ui::card class="bg-base-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-primary/10 rounded-lg">
                        <x-ui::icon name="tabler.calendar-check" class="w-8 h-8 text-primary" />
                    </div>
                    <div>
                        <div class="text-xs opacity-70">{{ __('Attendance') }}</div>
                        <div class="text-xl font-bold">{{ $complianceMetrics['attendance_score'] }}%</div>
                        <div class="text-[10px]">{{ $complianceMetrics['attended_days'] }} / {{ $complianceMetrics['total_days'] }} {{ __('days') }}</div>
                    </div>
                </div>
            </x-ui::card>
            <x-ui::card class="bg-base-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-secondary/10 rounded-lg">
                        <x-ui::icon name="tabler.book" class="w-8 h-8 text-secondary" />
                    </div>
                    <div>
                        <div class="text-xs opacity-70">{{ __('Journal Completion') }}</div>
                        <div class="text-xl font-bold">{{ $complianceMetrics['journal_score'] }}%</div>
                        <div class="text-[10px]">{{ $complianceMetrics['approved_journals'] }} / {{ $complianceMetrics['total_days'] }} {{ __('entries') }}</div>
                    </div>
                </div>
            </x-ui::card>
            <x-ui::card class="bg-base-200">
                <div class="flex items-center gap-4">
                    <div class="p-3 bg-accent/10 rounded-lg">
                        <x-ui::icon name="tabler.chart-pie" class="w-8 h-8 text-accent" />
                    </div>
                    <div>
                        <div class="text-xs opacity-70">{{ __('Compliance Score') }}</div>
                        <div class="text-xl font-bold">{{ $complianceMetrics['final_score'] }}%</div>
                        <div class="text-[10px]">{{ __('Participation weight: 50%') }}</div>
                    </div>
                </div>
            </x-ui::card>
        </div>

        <x-ui::card title="{{ __('Academic Evaluation') }}" shadow separator>
            <x-ui::form wire:submit="save">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($criteria as $key => $value)
                        <x-ui::input 
                            type="number" 
                            min="0" 
                            max="100" 
                            label="{{ ucfirst(str_replace('_', ' ', $key)) }}" 
                            wire:model="criteria.{{ $key }}" 
                        />
                    @endforeach
                </div>

                <x-ui::textarea 
                    label="{{ __('Feedback / Notes') }}" 
                    wire:model="feedback" 
                    rows="4" 
                    class="mt-4"
                />

                <x-slot:actions>
                    <x-ui::button label="{{ __('Cancel') }}" link="{{ route('teacher.dashboard') }}" />
                    <x-ui::button type="submit" label="{{ __('Submit Evaluation') }}" class="btn-primary" spinner="save" />
                </x-slot:actions>
            </x-ui::form>
        </x-ui::card>
    </x-ui::main>
</div>
