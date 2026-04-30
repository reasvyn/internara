<div>
    <x-ui::form wire:submit="save">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2">
                <x-ui::input 
                    label="{{ __('schedule::ui.agenda_title') }}" 
                    wire:model="data.title" 
                    placeholder="{{ __('schedule::ui.agenda_title_hint') }}" 
                    required 
                />
            </div>

            <div class="md:col-span-2">
                <x-ui::textarea 
                    label="{{ __('schedule::ui.description') }}" 
                    wire:model="data.description" 
                    placeholder="{{ __('schedule::ui.description_hint') }}" 
                    rows="3"
                />
            </div>

            <x-ui::select 
                label="{{ __('schedule::ui.agenda_type') }}" 
                wire:model="data.type" 
                :options="$types" 
                required 
            />

            <x-ui::input 
                label="{{ __('schedule::ui.location') }}" 
                wire:model="data.location" 
                placeholder="{{ __('schedule::ui.location_hint') }}" 
            />

            <x-ui::input 
                label="{{ __('schedule::ui.start_at') }}" 
                type="datetime-local" 
                wire:model="data.start_at" 
                required 
            />

            <x-ui::input 
                label="{{ __('schedule::ui.end_at') }}" 
                type="datetime-local" 
                wire:model="data.end_at" 
            />

            <div class="md:col-span-2">
                <x-ui::input 
                    label="{{ __('schedule::ui.academic_year') }}" 
                    wire:model="data.academic_year" 
                    readonly 
                    disabled
                />
            </div>
        </div>

        <x-slot:actions>
            <x-ui::button label="{{ __('schedule::ui.cancel') }}" class="btn-ghost" @click="$dispatch('close')" />
            <x-ui::button label="{{ __('schedule::ui.save_agenda') }}" icon="tabler.device-floppy" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-ui::form>
</div>