<x-ui::card :title="__('assessment::ui.evaluation.rubric_title')" shadow separator>
    <form wire:submit="submit" class="space-y-6">
        @foreach($competencies as $competency)
            <div class="space-y-2">
                <div class="flex justify-between items-center">
                    <label class="text-sm font-semibold text-gray-700">
                        {{ $competency['name'] }}
                    </label>
                    <span class="text-xs font-bold text-primary">{{ $scores[$competency['id']] }} / 100</span>
                </div>
                <div class="text-xs text-gray-500 mb-2 italic">
                    {{ $competency['description'] }}
                </div>
                <input 
                    type="range" 
                    min="0" 
                    max="100" 
                    wire:model.live="scores.{{ $competency['id'] }}" 
                    class="range range-xs range-primary" 
                    step="1" 
                />
                @error('scores.' . $competency['id']) 
                    <p class="text-xs text-error mt-1">{{ $message }}</p> 
                @enderror
            </div>
        @endforeach

        <hr class="my-4 opacity-50" />

        <div class="space-y-2">
            <x-ui::textarea 
                :label="__('assessment::ui.evaluation.feedback_notes')" 
                wire:model="feedback" 
                placeholder="..."
                rows="3"
                hint="Max 1000 characters"
            />
        </div>

        <div class="flex justify-end pt-4">
            <x-ui::button 
                type="submit" 
                :label="__('assessment::ui.evaluation.submit_action')" 
                class="btn-primary" 
                wire:loading.attr="disabled"
            />
        </div>
    </form>
</x-ui::card>
