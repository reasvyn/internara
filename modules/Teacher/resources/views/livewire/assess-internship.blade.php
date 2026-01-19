<div>
    <x-ui::header title="{{ __('Assess Student') }}" subtitle="{{ $registration->student->name }} - {{ $registration->placement->company_name }}" />

    <x-ui::main>
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
