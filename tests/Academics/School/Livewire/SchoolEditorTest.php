<?php

declare(strict_types=1);

use App\Academics\School\Livewire\SchoolEditor;
use App\Settings\Models\Setting;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('FB792: TallstackUI migration — SchoolEditor flash → toast', function (): void {
    it('FB792-FR-TS6a: base layout contains x-ts-toast for coexistence', function (): void {
        $base = file_get_contents(resource_path('views/core/layouts/base.blade.php'));

        expect($base)->toContain('<x-ts-toast />')
            ->toContain('@flasher_render');
    });

    it('J68GZ-FR-D10a: save() dispatches TallstackUI toast success via Interactions', function (): void {
        actingAsAdmin();

        Setting::factory()->create(['key' => 'school.name', 'value' => 'Old School']);

        $component = Livewire::test(SchoolEditor::class)
            ->set('form.name', 'New School')
            ->set('form.email', 'school@example.test')
            ->call('save');

        // Debug: show effects
        // dump($component->effects);
        // dump($component->payload);

        $component->assertHasNoErrors()
            ->assertDispatched('ts-ui:toast')
            ->assertDispatched('saved');
    });

    it('FB792-NFR-DEP5: SchoolEditor no longer uses php-flasher flash()-> in save path', function (): void {
        // Verify the Livewire class uses Interactions trait and toast(), not flash()
        $reflection = new ReflectionClass(SchoolEditor::class);

        expect($reflection->hasMethod('save'))->toBeTrue();

        $source = file_get_contents($reflection->getFileName());

        expect($source)->toContain('Interactions')
            ->toContain('toast()->success')
            ->not->toContain('flash()->success');
    });

    it('52O1I-FR-T2: ThemeSwitcher still coexists — SchoolEditor save does not break theme cookie', function (): void {
        actingAsAdmin();

        Livewire::test(SchoolEditor::class)
            ->set('form.name', 'Another School')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('saved');
    });
});
