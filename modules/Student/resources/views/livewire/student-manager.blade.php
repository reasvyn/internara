<div
    x-data="{
        search: $wire.entangle('search', true),
        selectedIds: $wire.entangle('selectedIds'),
        applyFilter() {
            let term = this.search.toLowerCase();
            let rows = this.$el.querySelectorAll('table tbody tr:not(.mary-table-empty)');
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
            });
        }
    }"
    x-init="$watch('search', () => applyFilter())"
>
    <x-ui::header
        wire:key="student-manager-header"
        :title="$title"
        :subtitle="__('user::ui.manager.subtitle')"
        :context="'admin::ui.menu.students'"
    >
        <x-slot:actions wire:key="student-manager-actions">
            <div class="flex items-center gap-3 relative z-50">
                <x-ui::dropdown icon="tabler.dots" variant="tertiary" right>
                    <x-ui::menu-item title="ui::common.print" icon="tabler.printer" wire:click="printPdf" />
                    <x-ui::menu-item title="ui::common.export" icon="tabler.download" wire:click="exportCsv" />
                </x-ui::dropdown>

                <x-ui::button :label="__('ui::common.refresh')" icon="tabler.refresh" variant="secondary" wire:click="refreshRecords" spinner="refreshRecords" />

                <div x-bind:class="{ 'pointer-events-none opacity-50': selectedIds.length === 0 }">
                    <x-ui::dropdown
                        :label="__('ui::common.bulk_actions')"
                        icon="tabler.layers-intersect"
                        variant="secondary"
                        :disabled="count($this->selectedIds ?? []) === 0"
                    >
                        <x-ui::menu-item
                            :title="__('student::ui.manager.bulk.send_setup_links')"
                            icon="tabler.mail-share"
                            wire:click="sendSelectedPasswordResetLinks"
                        />
                        <x-ui::menu-item
                            :title="__('student::ui.manager.bulk.activate_selected')"
                            icon="tabler.user-check"
                            class="text-success"
                            wire:click="activateSelected"
                        />
                        <x-ui::menu-item
                            :title="__('student::ui.manager.bulk.archive_selected')"
                            icon="tabler.archive"
                            class="text-warning"
                            wire:click="archiveSelected"
                        />
                        <x-ui::menu-item
                            title="ui::common.delete_selected"
                            icon="tabler.trash"
                            class="text-error"
                            wire:click="removeSelected"
                            wire:confirm="{{ __('ui::common.delete_confirm') }}"
                        />
                    </x-ui::dropdown>
                </div>

                <x-ui::button :label="__('user::ui.manager.add_student')" icon="tabler.plus" variant="primary" wire:click="add" />
            </div>
        </x-slot:actions>
    </x-ui::header>

    <x-ui::card>
        <div>
            <div class="mb-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <div class="w-full md:w-1/3">
                    <x-ui::input
                        :placeholder="__('user::ui.manager.search_placeholder')"
                        icon="tabler.search"
                        wire:model.live.debounce.250ms="search"
                        x-model="search"
                        clearable
                    />
                </div>

                <x-ui::dropdown :close-on-content-click="false" right>
                    <x-slot:trigger>
                        <x-ui::button icon="tabler.filter" variant="secondary" class="gap-2">
                            <span>{{ __('user::ui.manager.filters.open') }}</span>
                            @if($this->activeFilterCount() > 0)
                                <x-ui::badge :value="$this->activeFilterCount()" variant="info" class="badge-sm" />
                            @endif
                        </x-ui::button>
                    </x-slot:trigger>

                    <div class="w-[min(92vw,30rem)] space-y-4 p-2">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <x-ui::select
                                :label="__('student::ui.manager.table.department')"
                                icon="tabler.school"
                                wire:model.live="filters.department_id"
                                :options="$this->departments"
                                :placeholder="__('student::ui.manager.filters.all_departments')"
                            />

                            <x-ui::select
                                :label="__('user::ui.manager.filters.status')"
                                icon="tabler.circle-check"
                                wire:model.live="filters.status"
                                :options="[
                                    ['id' => 'active', 'name' => __('user::ui.manager.form.active')],
                                    ['id' => 'pending', 'name' => __('user::ui.manager.form.pending')],
                                    ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')],
                                ]"
                                :placeholder="__('user::ui.manager.filters.all_statuses')"
                            />

                            <x-ui::input
                                :label="__('user::ui.manager.filters.created_from')"
                                icon="tabler.calendar-down"
                                type="date"
                                wire:model.live="filters.created_from"
                            />

                            <x-ui::input
                                :label="__('user::ui.manager.filters.created_to')"
                                icon="tabler.calendar-up"
                                type="date"
                                wire:model.live="filters.created_to"
                            />
                        </div>

                        <div class="flex justify-end">
                            <x-ui::button
                                :label="__('user::ui.manager.filters.reset')"
                                icon="tabler.filter-off"
                                variant="secondary"
                                wire:click="resetFilters"
                            />
                        </div>
                    </div>
                </x-ui::dropdown>
            </div>

            <div class="w-full overflow-auto rounded-xl border border-base-200 bg-base-100 shadow-sm max-h-[60vh] relative">
                <div wire:loading.flex wire:target="search,refreshRecords,filters" class="absolute inset-0 bg-base-100/60 backdrop-blur-[1px] z-20 items-center justify-center">
                    <span class="loading loading-spinner loading-md text-base-content/20"></span>
                </div>

                <x-mary-table
                    class="table-zebra table-md"
                    :headers="$this->headers"
                    :rows="$this->records"
                    wire:model="selectedIds"
                    :sort-by="$this->sortBy"
                    selectable
                    with-pagination
                >
                    @scope('cell_name', $user)
                        <div class="flex items-center gap-3">
                            <x-ui::avatar :image="$user->avatar_url" :title="$user->name" size="w-8" />
                            <div class="font-semibold">{{ $user->name }}</div>
                        </div>
                    @endscope

                    @scope('cell_registration_number', $user)
                        <span>{{ $user->registration_number ?: __('ui::common.not_applicable') }}</span>
                    @endscope

                    @scope('cell_department_name', $user)
                        <span>{{ $user->department_name ?: __('ui::common.not_applicable') }}</span>
                    @endscope

                    @scope('cell_display_status', $user)
                        <x-ui::badge
                            :value="__('user::ui.manager.form.' . $user->display_status)"
                            :variant="$this->statusBadgeVariant($user->display_status)"
                            class="badge-sm"
                        />
                    @endscope

                    @scope('cell_created_at', $user)
                        <span>{{ \Illuminate\Support\Carbon::parse($user->created_at)->translatedFormat('d M Y') }}</span>
                    @endscope

                    @scope('actions', $user)
                        <div class="flex justify-end gap-2">
                            <x-ui::button
                                icon="tabler.mail-share"
                                variant="tertiary"
                                wire:click="sendPasswordResetLink('{{ $user->id }}')"
                                class="text-warning btn-xs"
                                tooltip="{{ __('user::ui.manager.form.send_setup_link') }}"
                            />
                            <x-ui::button icon="tabler.edit" variant="tertiary" wire:click="edit('{{ $user->id }}')" class="text-info btn-xs" tooltip="{{ __('user::ui.manager.edit_student') }}" />
                            <x-ui::button
                                icon="tabler.trash"
                                variant="tertiary"
                                wire:click="discard('{{ $user->id }}')"
                                wire:confirm="{{ __('ui::common.delete_confirm') }}"
                                class="text-error btn-xs"
                                tooltip="{{ __('ui::common.delete') }}"
                            />
                        </div>
                    @endscope
                </x-mary-table>
            </div>
        </div>
    </x-ui::card>

    <x-ui::modal wire:model="formModal" :title="$form->id ? __('user::ui.manager.edit_student') : __('user::ui.manager.add_student')">
        <x-ui::form wire:submit="save">
            <div class="space-y-4">
                <x-ui::input :label="__('user::ui.manager.form.full_name')" icon="tabler.signature" wire:model="form.name" required />

                <x-ui::input :label="__('user::ui.manager.form.email')" icon="tabler.mail" type="email" wire:model="form.email" required />

                @if($form->id)
                    <x-ui::input :label="__('user::ui.manager.form.username')" icon="tabler.at" wire:model="form.username" readonly />
                @endif

                <x-ui::alert type="info" icon="tabler.lock">
                    {{ $form->id ? __('student::ui.manager.form.password_reset_notice') : __('student::ui.manager.form.password_setup_notice') }}
                </x-ui::alert>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui::input :label="__('user::ui.manager.form.nisn')" icon="tabler.id" wire:model="form.profile.national_identifier" placeholder="e.g. NISN" />
                    <x-ui::input :label="__('user::ui.manager.form.nis')" icon="tabler.id-badge-2" wire:model="form.profile.registration_number" placeholder="e.g. NIS" />
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui::select
                        :label="__('user::ui.manager.form.department')"
                        icon="tabler.school"
                        wire:model="form.profile.department_id"
                        :options="$this->departments"
                        :placeholder="__('user::ui.manager.form.select_department')"
                    />
                    <x-ui::input :label="__('user::ui.manager.form.phone')" icon="tabler.phone" wire:model="form.profile.phone" />
                </div>

                <x-ui::textarea :label="__('user::ui.manager.form.address')" icon="tabler.map-pin" wire:model="form.profile.address" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui::select
                        :label="__('user::ui.manager.form.gender')"
                        icon="tabler.gender-intersex"
                        wire:model="form.profile.gender"
                        :options="[
                            ['id' => 'male', 'name' => __('profile::enums.gender.male')],
                            ['id' => 'female', 'name' => __('profile::enums.gender.female')],
                        ]"
                        :placeholder="__('user::ui.manager.form.select_gender')"
                    />
                    <x-ui::select
                        :label="__('user::ui.manager.form.blood_type')"
                        icon="tabler.droplet"
                        wire:model="form.profile.blood_type"
                        :options="[
                            ['id' => 'A', 'name' => 'A'],
                            ['id' => 'B', 'name' => 'B'],
                            ['id' => 'AB', 'name' => 'AB'],
                            ['id' => 'O', 'name' => 'O'],
                        ]"
                        :placeholder="__('user::ui.manager.form.select_blood_type')"
                    />
                </div>

                <x-ui::select
                    :label="__('user::ui.manager.form.status')"
                    icon="tabler.circle-check"
                    wire:model="form.status"
                    :options="[
                        ['id' => 'pending', 'name' => __('user::ui.manager.form.pending')],
                        ['id' => 'active', 'name' => __('user::ui.manager.form.active')],
                        ['id' => 'inactive', 'name' => __('user::ui.manager.form.inactive')],
                    ]"
                />

                <p class="text-xs text-base-content/60">
                    {{ __('student::ui.manager.form.archive_hint') }}
                </p>
            </div>

            <x-slot:actions>
                <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('formModal', false)" />
                <x-ui::button :label="__('ui::common.save')" type="submit" variant="primary" spinner="save" />
            </x-slot:actions>
        </x-ui::form>
    </x-ui::modal>

    <x-ui::modal wire:model="confirmModal" :title="__('user::ui.manager.delete.title')">
        <p>{{ __('user::ui.manager.delete.message') }}</p>
        <x-slot:actions>
            <x-ui::button :label="__('ui::common.cancel')" wire:click="$set('confirmModal', false)" />
            <x-ui::button :label="__('ui::common.delete')" class="btn-error" wire:click="remove('{{ $recordId }}')" spinner="remove" />
        </x-slot:actions>
    </x-ui::modal>
</div>
