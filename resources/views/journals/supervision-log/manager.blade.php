<div class="p-8">
    <x-mary-header
        :title="__('journals.supervision.logs_title')"
        :subtitle="__('journals.supervision.logs_subtitle')"
        separator
        progress-indicator
    />

    @if (! $registration)
        <div class="alert alert-warning">
            <x-ts-icon name="exclamation-triangle" class="h-5 w-5" />
            {{ __('journals.supervision.no_active_registration') }}
        </div>
    @else
        <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
            <div class="space-y-6 md:col-span-2">
                <x-ts-card class="bg-base-100 border-base-200 border">
                    @php
                        $headers = [
                            ['key' => 'date', 'label' => __('journals.date')],
                            ['key' => 'type', 'label' => __('journals.supervision.type')],
                            ['key' => 'topic', 'label' => __('journals.topic')],
                            ['key' => 'is_verified', 'label' => __('journals.status')],
                        ];
                    @endphp

                    <x-ts-table :headers="$headers" :rows="$logs" paginate>
                        @interact('column_date', $log)
                            {{ $log->date->format('d M Y') }}
                        @endinteract

                        @interact('column_type', $log)
                            <x-ts-badge
                                :text="ucfirst($log->type)"
                                :class="$log->type === 'guidance' ? 'badge-primary' : 'badge-secondary'"
                            />
                        @endinteract

                        @interact('column_is_verified', $log)
                            @if ($log->is_verified)
                                <x-ts-badge :text="__('journals.verified')" class="badge-success" />
                            @else
                                <x-ts-badge :text="__('journals.pending')" class="badge-neutral" />
                            @endif
                        @endinteract
                    </x-ts-table>
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
            </div>
        </div>
    @endif
</div>
