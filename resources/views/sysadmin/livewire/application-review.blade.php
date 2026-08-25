<div>
    <x-core::ui.page-header
        :title="__('internship.applications.title')"
        :description="__('internship.applications.subtitle')"
    />

    <x-ts-card shadowless>
        @if ($this->pendingApplications->isEmpty())
            <x-ts-alert
                :title="__('internship.applications.empty')"
                :text="__('internship.applications.empty_desc')"
                icon="check-circle"
                color="info"
            />
        @else
            @php
                $headers = [
                    ['key' => 'name', 'label' => __('internship.applications.name')],
                    ['key' => 'email', 'label' => __('internship.applications.email')],
                    ['key' => 'department', 'label' => __('internship.applications.program')],
                    ['key' => 'created_at', 'label' => __('internship.applications.submitted')],
                    ['key' => 'action', 'label' => ''],
                ];
            @endphp

            <x-ts-table :headers="$headers" :rows="$this->pendingApplications">
                @interact('column_name', $app)
                    <span class="font-medium">{{ $app->name }}</span>
                @endinteract

                @interact('column_email', $app)
                    <span class="text-sm">{{ $app->email }}</span>
                @endinteract

                @interact('column_department', $app)
                    <span class="text-sm">{{ $app->department?->name ?? '—' }}</span>
                @endinteract

                @interact('column_created_at', $app)
                    <span class="text-base-content/60 text-xs">{{ $app->created_at->diffForHumans() }}</span>
                @endinteract

                @interact('column_action', $app)
                    <div class="flex justify-end gap-2">
                        <x-ts-button
                            :text="__('internship.applications.approve')"
                            wire:click="approve('{{ $app->id }}')"
                            icon="check"
                            color="green"
                            sm
                        />
                        <x-ts-button
                            :text="__('internship.applications.reject')"
                            wire:click="confirmReject('{{ $app->id }}')"
                            icon="x-mark"
                            color="red"
                            sm
                        />
                    </div>
                @endinteract
            </x-ts-table>
        @endif

        <x-ts-modal wire="showRejectModal" :title="__('internship.applications.reject_title')" blur>
            <form wire:submit="reject" class="space-y-4">
                <x-ts-textarea
                    :label="__('internship.applications.rejection_reason')"
                    wire:model="rejectionReason"
                    required
                />
                <div class="mt-6 flex justify-end gap-2">
                    <x-ts-button
                        :text="__('internship.applications.cancel')"
                        wire:click="$set('showRejectModal', false)"
                        color="white"
                        sm
                    />
                    <x-ts-button
                        :text="__('internship.applications.reject')"
                        type="submit"
                        icon="x-mark"
                        color="red"
                        sm
                    />
                </div>
            </form>
        </x-ts-modal>

        @include('sysadmin.components.application-review-guide')
    </x-ts-card>
</div>
