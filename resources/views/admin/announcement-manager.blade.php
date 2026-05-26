<div class="py-4">
    <div class="mb-6 flex items-start sm:items-center justify-between flex-col sm:flex-row gap-4">
        <div>
            <h2 class="text-xl font-bold">{{ __('announcement.title') }}</h2>
            <p class="text-sm text-base-content/50 mt-1">{{ __('announcement.subtitle') }}</p>
        </div>
        <x-mary-button :label="__('announcement.create')" icon="o-plus" class="btn-primary btn-sm" wire:click="$set('showForm', true)" />
    </div>

    @if($showForm)
        <x-mary-card class="bg-base-100 border border-base-content/10 mb-6">
            <x-mary-form wire:submit="save">
                <div class="space-y-5">
                    <x-mary-input :label="__('announcement.fields.title')" wire:model="form.title" />
                    <x-shared::ui.markdown-editor :label="__('announcement.fields.message')" model="form.message" rows="6" :hint="__('announcement.markdown_hint')" />
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-mary-select :label="__('announcement.fields.type')" wire:model="form.type"
                            :options="[
                                ['id' => 'info', 'name' => 'Info'],
                                ['id' => 'success', 'name' => 'Success'],
                                ['id' => 'warning', 'name' => 'Warning'],
                                ['id' => 'error', 'name' => 'Error'],
                            ]"
                        />
                        <x-mary-input :label="__('announcement.fields.link')" wire:model="form.link" placeholder="https://..." />
                    </div>

                    <div class="border-t border-base-content/10 pt-4 space-y-4">
                        <p class="text-sm font-medium">{{ __('announcement.delivery') }}</p>
                        <x-mary-radio
                            wire:model.live="form.status"
                            :options="[
                                ['id' => 'draft', 'name' => __('announcement.status.draft')],
                                ['id' => 'scheduled', 'name' => __('announcement.status.scheduled')],
                                ['id' => 'published', 'name' => __('announcement.status.published')],
                            ]"
                        />

                        @if($form->status === 'scheduled')
                            <x-mary-input
                                :label="__('announcement.fields.scheduled_at')"
                                type="datetime-local"
                                wire:model="form.scheduled_at"
                                :hint="__('announcement.schedule_hint')"
                            />
                        @endif

                        <x-mary-toggle :label="__('announcement.send_to_all')" wire:model.live="form.sendToAll" />

                        @if(!$form->sendToAll)
                            <div class="mt-4">
                                <x-mary-choices :label="__('announcement.fields.target_roles')" wire:model="form.target_roles" :options="$roles" multiple :hint="__('announcement.roles_hint')" />
                            </div>
                        @endif
                    </div>
                </div>

                <x-slot:actions>
                    <x-mary-button :label="__('common.actions.cancel')" wire:click="resetForm" class="btn-ghost btn-sm" />
                    <x-mary-button :label="__('announcement.send')" type="submit" class="btn-primary btn-sm" icon-right="o-paper-airplane" spinner="save" />
                </x-slot:actions>
            </x-mary-form>
        </x-mary-card>
    @endif

    <x-mary-card class="bg-base-100 border border-base-content/10">
        @if($announcements->isEmpty())
            <div class="text-center py-12 text-sm text-base-content/40">
                {{ __('announcement.empty') }}
            </div>
        @else
            <div class="divide-y divide-base-content/10">
                @foreach($announcements as $announcement)
                    <div class="py-4 flex items-start justify-between gap-4">
                        <div class="flex items-start gap-3 min-w-0">
                            <div @class([
                                'size-8 rounded-lg flex items-center justify-center shrink-0',
                                'bg-info/10 text-info' => $announcement->type === 'info',
                                'bg-success/10 text-success' => $announcement->type === 'success',
                                'bg-warning/10 text-warning' => $announcement->type === 'warning',
                                'bg-error/10 text-error' => $announcement->type === 'error',
                            ])>
                                <x-mary-icon :name="match($announcement->type) {
                                    'success' => 'o-check-circle',
                                    'warning' => 'o-exclamation-triangle',
                                    'error' => 'o-x-circle',
                                    default => 'o-information-circle',
                                }" class="size-4" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-medium">{{ $announcement->title }}</h4>
                                    <x-mary-badge :value="__('announcement.status.' . $announcement->status->value)" class="badge-sm
                                        @if($announcement->isDraft()) badge-ghost
                                        @elseif($announcement->isScheduled()) badge-warning
                                        @else badge-success
                                        @endif" />
                                </div>
                                <div class="text-xs text-base-content/60 mt-0.5 line-clamp-2 prose prose-sm max-w-none">{!! Str::markdown($announcement->message) !!}</div>
                                <p class="text-[10px] text-base-content/40 mt-1.5">
                                    {{ $announcement->created_at->format('d M Y H:i') }}
                                    @if($announcement->isScheduled() && $announcement->scheduled_at)
                                        &middot; {{ __('announcement.scheduled_for') }} {{ $announcement->scheduled_at->format('d M Y H:i') }}
                                    @endif
                                    @if($announcement->target_roles)
                                        &middot; {{ implode(', ', $announcement->target_roles) }}
                                    @else
                                        &middot; {{ __('announcement.all_users') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            @if($announcement->isDraft() || $announcement->isScheduled())
                                <x-mary-button icon-right="o-paper-airplane" class="btn-ghost btn-sm text-success"
                                    wire:click="confirmPublish('{{ $announcement->id }}')"
                                    :aria-label="__('announcement.publish_now')" />
                            @endif
                            <x-mary-button icon="o-trash" class="btn-ghost btn-sm text-error"
                                wire:click="confirmDelete('{{ $announcement->id }}')"
                                :aria-label="__('common.actions.delete')" />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-mary-card>

    <x-shared::ui.confirm
        :title="$confirmActionType === 'delete' ? __('common.actions.confirm_action') : __('announcement.publish_now')"
        :message="$confirmActionType === 'delete' ? __('announcement.confirm_delete') : __('announcement.confirm_publish')"
        :confirmText="$confirmActionType === 'delete' ? __('common.actions.delete') : __('announcement.publish_now')"
        confirmClass="btn-error"
    />
</div>
