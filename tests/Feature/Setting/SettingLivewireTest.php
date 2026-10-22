<?php

declare(strict_types=1);

namespace Tests\Feature\Setting;

use App\Modules\Setting\Domain\Locale\Livewire\LangSwitch;
use App\Modules\Setting\Livewire\SystemSetting;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('52O1I: Setting Livewire UI Components', function (): void {
    test('52O1I-FR-SET-001: SystemSetting renders form and tabs for authorized administrator', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $test = Livewire::test(SystemSetting::class);
        $test->assertOk()
            ->assertSet('isDirty', false)
            ->assertSet('showConfirm', false);
    });

    test('52O1I-FR-SET-002: LangSwitch exposes available locales and updates locale', function (): void {
        $test = Livewire::test(LangSwitch::class);
        $test->assertOk();

        $locales = $test->instance()->getAvailableLocalesProperty();
        expect($locales)->toHaveKeys(['en', 'id']);

        $test->call('changeLocale', 'id')
            ->assertDispatched('language-changed', locale: 'id');

        expect($test->get('locale'))->toBe('id');
    });
});
