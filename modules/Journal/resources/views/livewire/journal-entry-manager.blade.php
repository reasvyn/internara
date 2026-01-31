<div>
    <x-ui::header 
        title="{{ $form->id ? __('Edit Jurnal') : __('Buat Jurnal Baru') }}" 
        subtitle="{{ __('Catat aktivitas harian Anda dengan lengkap dan jujur.') }}" 
    />

    <x-ui::main>
        <div class="max-w-3xl mx-auto">
            <x-ui::card>
                <x-ui::form wire:submit="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input 
                            label="{{ __('Tanggal') }}" 
                            type="date" 
                            wire:model="form.date" 
                            required 
                        />
                    </div>

                    <x-ui::input 
                        label="{{ __('Topik Pekerjaan') }}" 
                        placeholder="{{ __('Misal: Instalasi OS, Troubleshooting Jaringan, dll') }}"
                        wire:model="form.work_topic" 
                        required 
                    />

                    <x-ui::textarea 
                        label="{{ __('Deskripsi Aktivitas') }}" 
                        placeholder="{{ __('Jelaskan apa saja yang Anda kerjakan hari ini...') }}"
                        wire:model="form.activity_description" 
                        rows="5"
                        required 
                    />

                    <div class="grid grid-cols-1 gap-4">
                        <x-ui::choices
                            label="{{ __('Kompetensi / Skill yang Dilatih') }}"
                            wire:model="form.competency_ids"
                            :options="$availableCompetencies"
                            placeholder="{{ __('Pilih kompetensi...') }}"
                            hint="{{ __('Anda dapat memilih lebih dari satu kompetensi') }}"
                        />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-ui::input 
                            label="{{ __('Nilai-nilai Karakter') }}" 
                            placeholder="{{ __('Misal: Disiplin, Tanggung Jawab') }}"
                            wire:model="form.character_values" 
                        />
                    </div>

                    <x-ui::textarea 
                        label="{{ __('Refleksi & Pembelajaran') }}" 
                        placeholder="{{ __('Apa hal baru yang Anda pelajari hari ini?') }}"
                        wire:model="form.reflection" 
                        rows="3"
                    />

                    <x-ui::file
                        label="{{ __('Lampiran / Bukti') }}"
                        wire:model="form.attachments"
                        multiple
                        accept="image/*,application/pdf"
                    />
                    <x-slot:actions>
                        <x-ui::button 
                            label="{{ __('Batal') }}" 
                            link="{{ route('journal.index') }}" 
                            class="btn-ghost" 
                        />
                        <x-ui::button 
                            label="{{ __('Simpan sebagai Draf') }}" 
                            wire:click="save(true)" 
                            class="btn-outline" 
                            spinner="save(true)" 
                        />
                        <x-ui::button 
                            label="{{ __('Kirim Jurnal') }}" 
                            wire:click="save(false)" 
                            class="btn-primary" 
                            spinner="save(false)" 
                        />
                    </x-slot:actions>
                </x-ui::form>
            </x-ui::card>
        </div>
    </x-ui::main>
</div>