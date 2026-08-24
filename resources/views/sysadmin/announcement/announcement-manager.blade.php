<div class="py-4">
    <div class="mb-6 flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
            <h2 class="text-xl font-bold">{{ __('announcement.title') }}</h2>
            <p class="text-base-content/50 mt-1 text-sm">{{ __('announcement.subtitle') }}</p>
        </div>
        <x-ts-button
            :text="__('announcement.create')"
            icon="plus"
            color="primary"
            sm
            wire:click="$set('showForm', true)"
        />
    </div>

    @if ($showForm)
        <x-ts-card shadowless class="bg-base-100 border-base-content/10 mb-6 border">
            <form wire:submit="save">
                <div class="space-y-5">
                    <x-ts-input :label="__('announcement.fields.title')" wire:model="form.title" />
                    <x-core::ui.markdown-editor
                        :label="__('announcement.fields.message')"
                        model="form.message"
                        rows="6"
                        :hint="__('announcement.markdown_hint')"
                    />
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ts-select.native
                            :label="__('announcement.fields.type')"
                            wire:model="form.type"
                            :options="[
                                ['id' => 'info', 'name' => __('announcement.types.info')],
                                ['id' => 'success', 'name' => __('announcement.types.success')],
                                ['id' => 'warning', 'name' => __('announcement.types.warning')],
                                ['id' => 'error', 'name' => __('announcement.types.error')],
                            ]"
                        />
                        <x-ts-input
                            :label="__('announcement.fields.link')"
                            wire:model="form.link"
                            :placeholder="__('announcement.fields.link_placeholder')"
                        />
                    </div>

                    <div class="border-base-content/10 space-y-4 border-t pt-4">
                        <p class="text-sm font-medium">{{ __('announcement.delivery') }}</p>
                        <x-ts-radio
                            wire:model.live="form.status"
                            :options="[
                                ['id' => 'draft', 'name' => __('announcement.status.draft')],
                                ['id' => 'scheduled', 'name' => __('announcement.status.scheduled')],
                                ['id' => 'published', 'name' => __('announcement.status.published')],
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
                                <x-mary-choices
                                    :label="__('announcement.fields.target_roles')"
                                    wire:model="form.target_roles"
                                    :options="$roles"
                                    multiple
                                    :hint="__('announcement.roles_hint')"
                                />
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button :text="__('common.actions.cancel')" wire:click="resetForm" color="white" sm />
                    <x-ts-button
                        :text="__('announcement.send')"
                        type="submit"
                        color="primary"
                        sm
                        icon-right="o-paper-airplane"
                        loading="save"
                    />
                </div>
            </form>

    @endif

    <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
        @if ($announcements->isEmpty())
            <div class="text-base-content/40 py-12 text-center text-sm">{{ __('announcement.empty') }}</div>
        @else
            <div class="divide-base-content/10 divide-y">
                @foreach ($announcements as $announcement)
                    <div class="flex items-start justify-between gap-4 py-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <div @class([
                                'size-8 rounded-lg flex items-center justify-center shrink-0',
                                'bg-info/10 text-info' => $announcement->type === 'info',
                                'bg-success/10 text-success' => $announcement->type === 'success',
                                'bg-warning/10 text-warning' => $announcement->type === 'warning',
                                'bg-error/10 text-error' => $announcement->type === 'error',
                            ])>
                                <x-ts-icon
                                    :name="match($announcement->type) {
                                    'success' => 'o-check-circle',
                                    'warning' => 'o-exclamation-triangle',
                                    'error' => 'o-x-circle',
                                    default => 'o-information-circle',
                                }"
                                    class="size-4"
                                />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h4 class="text-sm font-medium">{{ $announcement->title }}</h4>
                                    <x-ts-badge
                                        :text="__('announcement.status.'.$announcement->status->value)"
                                        class="badge-sm
                                        @if($announcement->isDraft()) badge-ghost
                                        @elseif($announcement->isScheduled()) badge-warning
                                        @else badge-success
                                        @endif"
                                    />
                                </div>
                                <div class="text-base-content/60 prose prose-sm mt-0.5 line-clamp-2 max-w-none text-xs">
                                    {!! Str::markdown($announcement->message, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                                </div>
                                <p class="text-base-content/40 mt-1.5 text-[10px]">
                                    {{ $announcement->created_at->format('d M Y H:i') }}
                                    @if ($announcement->isScheduled() && $announcement->scheduled_at)
                                        &middot; {{ __('announcement.scheduled_for') }} {{ $announcement->scheduled_at->format('d M Y H:i') }}
                                    @endif
                                    @if ($announcement->target_roles)
                                        &middot; {{ implode(', ', $announcement->target_roles) }}
                                    @else
                                        &middot; {{ __('announcement.all_users') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            @if ($announcement->isDraft() || $announcement->isScheduled())
                                <x-ts-button
                                    icon-right="o-paper-airplane"
                                    class="text-success"
                                    color="white"
                                    sm
                                    wire:click="confirmPublish('{{ $announcement->id }}')"
                                    :aria-label="__('announcement.publish_now')"
                                />
                            @endif
                            <x-ts-button
                                icon="trash"
                                class="text-error"
                                color="white"
                                sm
                                wire:click="confirmDelete('{{ $announcement->id }}')"
                                :aria-label="__('common.actions.delete')"
                            />
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        @include('sysadmin.announcement.components.announcement-guide')
</div>
