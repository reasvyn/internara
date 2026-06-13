<div>
    <x-mary-header :title="__('internship.applications.title')" :subtitle="__('internship.applications.subtitle')" separator />

    <x-mary-card>
        @if($this->pendingApplications->isEmpty())
            <x-mary-alert :title="__('internship.applications.empty')" :description="__('internship.applications.empty_desc')" icon="o-check-circle" />
        @else
            <div class="overflow-x-auto">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>{{ __('internship.applications.name') }}</th>
                            <th>{{ __('internship.applications.email') }}</th>
                            <th>{{ __('internship.applications.program') }}</th>
                            <th>{{ __('internship.applications.school') }}</th>
                            <th>{{ __('internship.applications.submitted') }}</th>
                            <th>{{ __('internship.applications.subtitle') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($this->pendingApplications as $app)
                            <tr>
                                <td>{{ $app->name }}</td>
                                <td>{{ $app->email }}</td>
                                <td>{{ $app->internship?->name }}</td>
                                <td>{{ $app->school?->name ?? '-' }}</td>
                                <td>{{ $app->created_at->diffForHumans() }}</td>
                                <td>
                                    <div class="flex gap-2">
                                        <x-mary-button :label="__('internship.applications.approve')" wire:click="approve('{{ $app->id }}')" icon="o-check" class="btn-success btn-sm" />
                                        <x-mary-button :label="__('internship.applications.reject')" wire:click="confirmReject('{{ $app->id }}')" icon="o-x-mark" class="btn-error btn-sm" />
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-mary-card>

    <x-mary-modal wire:model="showRejectModal" :title="__('internship.applications.reject_title')">
        <x-mary-form wire:submit="reject">
            <x-mary-textarea :label="__('internship.applications.rejection_reason')" wire:model="rejectionReason" required />
            <x-slot:actions>
                <x-mary-button :label="__('internship.applications.cancel')" wire:click="$set('showRejectModal', false)" />
                <x-mary-button :label="__('internship.applications.reject')" type="submit" icon="o-x-mark" class="btn-error" />
            </x-slot:actions>
        </x-mary-form>
    </x-mary-modal>
</div>
