<x-ui::components.record-manager :title="__('certificate.title')" :subtitle="__('certificate.subtitle')">
    <x-slot:headerActions>
        <x-ts-button :text="__('certificate.add_template')" icon="plus" color="primary" sm wire:click="create" />
    </x-slot:headerActions>

    <div class="overflow-x-auto">
        <x-ts-table
            :headers="$this->headers()"
            :rows="$this->rows()"
            :sort-by="$sortBy"
            with-pagination
            class="table-sm"
        >
            @interact('column_is_active', $t)
                <x-ts-badge
                    :text="$t->is_active ? __('certificate.active') : __('certificate.inactive')"
                    :class="$t->is_active ? 'badge-success' : 'badge-ghost'"
                />
            @endinteract

            @interact('column_layout', $t)
                <span class="text-sm">{{ $t->layout }}</span>
            @endinteract

            @interact('column_action', $t)
                <div class="flex justify-end gap-1">
                    <x-ts-button
                        aria-label="{{ __('common.actions.edit') }}"
                        icon="pencil"
                        color="white"
                        sm
                        wire:click="edit('{{ $t->id }}')"
                    />
                </div>
            @endinteract
        </x-ts-table>
    </div>

    <x-slot:modal>
        <x-ts-modal wire="showModal" :title="__('certificate.template_form')" class="max-w-2xl blur">
            <form wire:submit="saveTemplate">
                <div class="space-y-5">
                    <x-ts-input :label="__('certificate.template_name')" wire:model="formData.name" />
                    <x-ts-select.native
                        :label="__('certificate.layout')"
                        wire:model="formData.layout"
                        :options="ts_options(['portrait' => __('certificate.layout_portrait'), 'landscape' => __('certificate.layout_landscape')])"
                    />
                    <x-ts-textarea
                        :label="__('certificate.content_template')"
                        wire:model="formData.content_template"
                        rows="10"
                    />
                    <x-ts-checkbox :label="__('certificate.is_active')" wire:model="formData.is_active" />
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('common.actions.cancel')"
                        wire:click="$set('showModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button
                        :text="__('certificate.save_template')"
                        color="primary"
                        sm
                        type="submit"
                        loading="saveTemplate"
                    />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>
    @include('certification.certificate.components.certificate-template-guide')
</x-ui::components.record-manager>
