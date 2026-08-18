<div class="mx-auto mt-6 max-w-2xl">
    <x-core::ui.page-header :title="__('incident.report_title')" :subtitle="__('incident.report_subtitle')" />

    <x-mary-form wire:submit="save">
        <x-mary-card>
            <div class="space-y-5">
                <x-mary-select
                    :label="__('incident.registration')"
                    wire:model="formData.registration_id"
                    :placeholder="__('incident.registration_placeholder')"
                    :options="$registrations"
                    option-label="internship.name"
                    option-value="id"
                />

                <x-mary-input :label="__('incident.date')" wire:model="formData.incident_date" type="datetime-local" />

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-mary-select
                        :label="__('incident.type')"
                        wire:model="formData.type"
                        :placeholder="__('incident.type_placeholder')"
                        :options="['accident' => __('incident.types.accident'), 'safety_violation' => __('incident.types.safety_violation'), 'harassment' => __('incident.types.harassment'), 'disciplinary' => __('incident.types.disciplinary'), 'other' => __('incident.types.other')]"
                    />
                    <x-mary-select
                        :label="__('incident.severity')"
                        wire:model="formData.severity"
                        :placeholder="__('incident.severity_placeholder')"
                        :options="['low' => __('incident.severities.low'), 'medium' => __('incident.severities.medium'), 'high' => __('incident.severities.high'), 'critical' => __('incident.severities.critical')]"
                    />
                </div>

                <x-mary-input
                    :label="__('incident.location')"
                    wire:model="formData.location"
                    :placeholder="__('incident.location_placeholder')"
                />
                <x-mary-textarea
                    :label="__('incident.description')"
                    wire:model="formData.description"
                    :placeholder="__('incident.description_placeholder')"
                    rows="4"
                />
                <x-mary-textarea
                    :label="__('incident.action_taken')"
                    wire:model="formData.action_taken"
                    :placeholder="__('incident.action_taken_placeholder')"
                    rows="3"
                />
            </div>

            <x-slot:actions>
                <x-mary-button :label="__('incident.submit')" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-mary-card>
    </x-mary-form>
</div>
