<div>
    @if ($registration)
        <x-ts-card shadowless class="bg-base-100 border-base-content/10 border">
            <x-slot:header>
                <span class="font-semibold">{{ __('registration.doc_upload_title') }}</span>
            </x-slot:header>

            <div class="space-y-4">
                @foreach ($documents as $document)
                    <div class="bg-base-200/30 border-base-content/10 flex items-start gap-4 rounded-lg border p-4">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium">{{ $document->title }}</p>
                            @php
                                $existing = $existingDocs->firstWhere('document_id', $document->id);
                            @endphp
                            @if ($existing)
                                <p class="text-success mt-1 text-xs">{{ __('registration.doc_uploaded') }}</p>
                            @else
                                <input
                                    type="file"
                                    wire:model="uploads.{{ $document->id }}"
                                    class="file-input file-input-bordered file-input-sm mt-2 w-full"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                />
                                @error("uploads.{$document->id}")
                                    <p class="text-error mt-1 text-xs">{{ $message }}</p>
                                @enderror
                            @endif
                        </div>
                    </div>
                @endforeach

                @if ($documents->isNotEmpty())
                    <div class="flex justify-end">
                        <x-ts-button wire:click="upload" :text="__('common.submit')" color="primary" />
                    </div>
                @else
                    <div class="text-base-content/50 py-6 text-center text-sm">
                        {{ __('registration.doc_no_requirements') }}
                    </div>
                @endif
            </div>
        </x-ts-card>

    @else
        <div class="text-base-content/60 py-12 text-center">
            <x-ts-icon name="document" class="mb-3 size-12" />
            <p class="text-sm font-medium">{{ __('registration.doc_no_registration') }}</p>
        </div>
    @endif
</div>
