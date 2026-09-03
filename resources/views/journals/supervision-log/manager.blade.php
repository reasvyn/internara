<div class="p-8">
    <x-ui::components.page-header
        :title="__('journals.supervision.logs_title')"
        :description="__('journals.supervision.logs_subtitle')"
    />

    @if (! $registration)
        <x-ts-alert
            color="warning"
            :text="__('journals.supervision.no_active_registration')"
            icon="exclamation-triangle"
        />
    @else
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div class="space-y-6 md:col-span-2">
                <x-ts-card class="bg-base-100 border-base-200 border">
                    @php
                        $headers = [
                            ['index' => 'date', 'label' => __('journals.date')],
                            ['index' => 'type', 'label' => __('journals.supervision.type')],
                            ['index' => 'topic', 'label' => __('journals.topic')],
                            ['index' => 'is_verified', 'label' => __('journals.status')],
                        ];
                    @endphp

                    <x-ts-table :headers="$headers" :rows="$logs" paginate>
                        @interact('column_date', $log)
                            {{ $log->date->format('d M Y') }}
                        @endinteract

                        @interact('column_type', $log)
                            <x-ts-badge
                                :text="ucfirst($log->type)"
                                :color="$log->type === 'guidance' ? 'primary' : 'secondary'"
                                xs
                            />
                        @endinteract

                        @interact('column_is_verified', $log)
                            @if ($log->is_verified)
                                <x-ts-badge :text="__('journals.verified')" color="green" xs />
                            @else
                                <x-ts-badge :text="__('journals.pending')" color="white" xs />
                            @endif
                        @endinteract
                    </x-ts-table>
                </x-ts-card>
            </div>

            <div class="space-y-6">
                <x-ts-card :header="__('journals.supervision.assigned_supervisors')" class="bg-base-200/50">
                    <div class="space-y-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-primary/10 rounded-lg p-2">
                                <x-ts-icon name="user-group" class="text-primary h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase opacity-50">
                                    {{ __('journals.supervision.teacher_supervisor') }}
                                </div>
                                <div class="font-medium">
                                    {{ $registration->teacher?->name ?? __('journals.supervision.not_assigned') }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="bg-secondary/10 rounded-lg p-2">
                                <x-ts-icon name="briefcase" class="text-secondary h-5 w-5" />
                            </div>
                            <div>
                                <div class="text-xs font-bold uppercase opacity-50">
                                    {{ __('journals.supervision.industry_mentor') }}
                                </div>
                                <div class="font-medium">
                                    {{ $registration->mentor?->name ?? __('journals.supervision.not_assigned') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </x-ts-card>
            </div>
        </div>
    @endif
</div>
