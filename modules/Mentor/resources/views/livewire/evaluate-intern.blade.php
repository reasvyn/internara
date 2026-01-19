<div>
    <x-ui::header title="{{ __('Evaluate Intern') }}" subtitle="{{ $registration->student->name }}" />

    <x-ui::main>
        <x-ui::card title="{{ __('Industry Assessment') }}" shadow separator>
            <x-ui::form wire:submit="save">
                <div class="space-y-4">
                    @foreach($criteria as $key => $value)
                        <div class="form-control">
                            <label class="label">
                                <span class="label-text font-bold">{{ ucfirst(str_replace('_', ' ', $key)) }} (0-100)</span>
                            </label>
                            <input 
                                type="range" 
                                min="0" 
                                max="100" 
                                wire:model.live="criteria.{{ $key }}" 
                                class="range range-primary range-sm" 
                            />
                            <div class="w-full flex justify-between text-xs px-2 mt-1">
                                <span>0</span>
                                <span class="font-bold text-lg text-primary">{{ $value }}</span>
                                <span>100</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <x-ui::textarea 
                    label="{{ __('Mentor Feedback') }}" 
                    wire:model="feedback" 
                    rows="3" 
                    class="mt-6"
                    placeholder="{{ __('Optional comments...') }}"
                />

                <x-slot:actions>
                    <x-ui::button label="{{ __('Cancel') }}" link="{{ route('mentor.dashboard') }}" />
                    <x-ui::button type="submit" label="{{ __('Submit') }}" class="btn-primary" spinner="save" />
                </x-slot:actions>
            </x-ui::form>
        </x-ui::card>
    </x-ui::main>
</div>
