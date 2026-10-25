<x-ui::components.record-manager :title="__('announcement.title')" :subtitle="__('announcement.subtitle')">
    <x-slot:headerActions>
        <x-ts-button
            :text="__('announcement.create')"
            icon="plus"
            color="primary"
            sm
            wire:click="$set('showForm', true)"
        />
    </x-slot:headerActions>

    <x-ts-table :headers="$this->headers()" :rows="$announcements" :sort-by="$sortBy" with-pagination class="table-sm">
        @interact('column_title', $announcement)
            <div class="flex flex-col py-1">
                <span class="text-sm font-semibold">{{ $announcement->title }}</span>
                <span class="text-base-content/60 line-clamp-1 text-xs">{{ strip_tags($announcement->message) }}</span>
            </div>
        @endinteract

        @interact('column_type', $announcement)
            @php
                $typeColor = match ($announcement->type) {
                    'success' => 'green',
                    'warning' => 'yellow',
                    'error' => 'red',
                    default => 'blue',
                };
            @endphp
            <x-ts-badge :text="__('announcement.types.'.$announcement->type)" :color="$typeColor" xs />
        @endinteract

        @interact('column_status', $announcement)
            @php
                $statusColor = match ($announcement->status->value) {
                    'published' => 'green',
                    'scheduled' => 'yellow',
                    default => 'gray',
                };
            @endphp
            <x-ts-badge :text="__('announcement.status.'.$announcement->status->value)" :color="$statusColor" xs />
        @endinteract

        @interact('column_created_at', $announcement)
            <span class="text-sm">{{ $announcement->created_at?->format('d M Y H:i') ?? '—' }}</span>
        @endinteract

        @interact('column_actions', $announcement)
            <x-ui::components.table-actions>
                @if ($announcement->isDraft() || $announcement->isScheduled())
                    <x-ui::components.table-action
                        action="custom"
                        icon="paper-airplane"
                        color="green"
                        wire:click="confirmPublish('{{ $announcement->id }}')"
                        :label="__('announcement.publish_now')"
                    />
                @endif
                <x-ui::components.table-action action="delete" wire:click="confirmDelete('{{ $announcement->id }}')" />
            </x-ui::components.table-actions>
        @endinteract
    </x-ts-table>

    <x-slot:modal>
        <x-ts-modal wire="showForm" :title="__('announcement.create')" separator blur class="max-w-3xl">
            <form wire:submit="save" class="space-y-4">
                <x-ts-input :label="__('announcement.fields.title')" wire:model="form.title" />

                <x-ui::components.markdown-editor
                    :label="__('announcement.fields.message')"
                    model="form.message"
                    rows="5"
                    :hint="__('announcement.markdown_hint')"
                />

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-ts-select.native
                        :label="__('announcement.fields.type')"
                        wire:model="form.type"
                        :options="ts_options([
                            ['id' => 'info', 'name' => __('announcement.types.info')],
                            ['id' => 'success', 'name' => __('announcement.types.success')],
                            ['id' => 'warning', 'name' => __('announcement.types.warning')],
                            ['id' => 'error', 'name' => __('announcement.types.error')],
                        ])"
                    />
                    <x-ts-input
                        :label="__('announcement.fields.link')"
                        wire:model="form.link"
                        :placeholder="__('announcement.fields.link_placeholder')"
                    />
                </div>

                <div class="border-base-content/10 space-y-4 border-t pt-4">
                    <p class="text-sm font-medium">{{ __('announcement.delivery') }}</p>
                    <x-ts-radio.group
                        wire:model.live="form.status"
                        :options="[
                            ['value' => 'draft', 'label' => __('announcement.status.draft')],
                            ['value' => 'scheduled', 'label' => __('announcement.status.scheduled')],
                            ['value' => 'published', 'label' => __('announcement.status.published')],
                        ]"
                    />

                    @if ($form->status === 'scheduled')
                        <x-ts-input
                            :label="__('announcement.fields.scheduled_at')"
                            type="datetime-local"
                            wire:model="form.scheduled_at"
                            :hint="__('announcement.schedule_hint')"
                        />
                    @endif

                    <x-ts-toggle :label="__('announcement.send_to_all')" wire:model.live="form.sendToAll" />

                    @if (! $form->sendToAll)
                        <div class="mt-4">
                            <x-ts-checkbox.group
                                :label="__('announcement.fields.target_roles')"
                                wire:model="form.target_roles"
                                :options="$roles"
                                multiple
                                :hint="__('announcement.roles_hint')"
                            />
                        </div>
                    @endif
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button :text="__('common.actions.cancel')" wire:click="resetForm" color="slate" outline sm />
                    <x-ts-button
                        :text="__('announcement.send')"
                        type="submit"
                        color="primary"
                        sm
                        icon-right="paper-airplane"
                        loading="save"
                    />
                </div>
            </form>
        </x-ts-modal>
    </x-slot:modal>

    <x-slot:auxiliary>
        @include('sysadmin.announcement.components.announcement-guide')
    </x-slot:auxiliary>
</x-ui::components.record-manager>
