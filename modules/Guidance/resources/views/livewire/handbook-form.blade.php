<div>
    <x-ui::form wire:submit="save">
        <div class="space-y-4">
            <x-ui::input 
                label="{{ __('guidance::ui.handbook_title') }}" 
                wire:model="data.title" 
                placeholder="{{ __('guidance::ui.handbook_title_hint') }}" 
                required 
            />

            <x-ui::textarea 
                label="{{ __('guidance::ui.description_hint') }}" 
                wire:model="data.description" 
                placeholder="{{ __('guidance::ui.description_hint') }}" 
                rows="2"
            />

            <div class="grid grid-cols-2 gap-4">
                <x-ui::input 
                    label="{{ __('guidance::ui.version_label') }}" 
                    wire:model="data.version" 
                    placeholder="1.0" 
                    required 
                />
                
                <div class="flex flex-col gap-2 justify-end pb-2">
                    <x-ui::checkbox label="{{ __('guidance::ui.is_mandatory') }}" wire:model="data.is_mandatory" tight />
                    <x-ui::checkbox label="{{ __('guidance::ui.is_active') }}" wire:model="data.is_active" tight />
                </div>
            </div>

            <x-ui::file 
                label="{{ __('guidance::ui.pdf_file') }}" 
                wire:model="file" 
                accept="application/pdf" 
                hint="{{ __('guidance::ui.file_hint') }}"
            />

            @if($handbookId && !$file)
                <div class="text-xs opacity-60 italic">
                    {{ __('guidance::ui.keep_empty_hint') }}
                </div>
            @endif
        </div>

        <x-slot:actions>
            <x-ui::button label="{{ __('guidance::ui.cancel') }}" class="btn-ghost" @click="$dispatch('close')" />
            <x-ui::button label="{{ __('guidance::ui.save_handbook') }}" icon="tabler.device-floppy" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-ui::form>
</div>
