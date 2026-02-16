<div>
    <x-ui::header 
        title="{{ $form->id ? __('journal::ui.index.actions.edit') : __('journal::ui.index.create_new') }}" 
        subtitle="{{ __('journal::ui.index.subtitle') }}" 
    />

    <x-ui::main>
        <div class="max-w-3xl mx-auto">
            <x-ui::card>
                <x-ui::form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input 
                            label="{{ __('journal::ui.index.form.date') }}" 
                            type="date" 
                            wire:model="form.date" 
                            required 
                            :hint="\Illuminate\Support\Carbon::parse($form->date)->translatedFormat('l')"
                        />
                    </div>

                    <x-ui::input 
                        label="{{ __('journal::ui.index.form.work_topic') }}" 
                        placeholder="{{ __('journal::ui.index.form.work_topic_placeholder') }}"
                        wire:model="form.work_topic" 
                        required 
                    />

                    <x-ui::textarea 
                        label="{{ __('journal::ui.index.form.description') }}" 
                        placeholder="{{ __('journal::ui.index.form.description_placeholder') }}"
                        wire:model="form.activity_description" 
                        rows="5"
                        required 
                    />

                    <div class="grid grid-cols-1 gap-4">
                        <x-ui::choices
                            label="{{ __('journal::ui.index.form.competence') }}"
                            wire:model="form.competency_ids"
                            :options="$availableCompetencies"
                            placeholder="{{ __('journal::ui.index.search_placeholder') }}"
                            hint="{{ __('journal::ui.index.form.competence_hint') }}"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input 
                            label="{{ __('journal::ui.index.form.character') }}" 
                            placeholder="{{ __('journal::ui.index.form.character_placeholder') }}"
                            wire:model="form.character_values" 
                        />
                    </div>

                    <x-ui::textarea 
                        label="{{ __('journal::ui.index.form.reflection') }}" 
                        placeholder="{{ __('journal::ui.index.form.reflection_placeholder') }}"
                        wire:model="form.reflection" 
                        rows="3"
                    />

                    <x-ui::textarea 
                        label="{{ __('journal::ui.index.form.notes') }}" 
                        placeholder="{{ __('journal::ui.index.form.notes_placeholder') }}"
                        wire:model="form.notes" 
                        rows="2"
                    />

                    <x-ui::file
                        label="{{ __('journal::ui.index.form.attachments') }}"
                        wire:model="form.attachments"
                        multiple
                        accept="image/*,application/pdf"
                    />
                    <x-slot:actions>
                        <x-ui::button 
                            label="{{ __('ui::common.cancel') }}" 
                            link="{{ route('journal.index') }}" 
                            class="btn-ghost" 
                        />
                        <x-ui::button 
                            label="{{ __('journal::ui.index.form.save_draft') }}" 
                            wire:click="save(true)" 
                            class="btn-outline" 
                            spinner="save(true)" 
                        />
                        <x-ui::button 
                            label="{{ __('journal::ui.index.form.submit') }}" 
                            wire:click="save(false)" 
                            class="btn-primary" 
                            spinner="save(false)" 
                        />
                    </x-slot:actions>
                </x-ui::form>
            </x-ui::card>
        </div>
    </x-ui::main>
</div>