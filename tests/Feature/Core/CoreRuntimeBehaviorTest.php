<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(LazilyRefreshDatabase::class);

describe('ZT6VS: remaining Core runtime behavior', function (): void {
    test('ZT6VS-FR-CORE-006: runtime foreign keys declare deletion behavior', function (): void {
        foreach (['registrations', 'placements', 'activity_log'] as $table) {
            foreach (Schema::getForeignKeys($table) as $foreignKey) {
                expect($foreignKey['on_delete'] ?? null)->not->toBeNull();
            }
        }
    });

    test('ZT6VS-FR-CORE-017/018/020/021 and ZT6VS-NFR-CORE-001: session runtime carries secure defaults and request state', function (): void {
        expect(config('session.lifetime'))->toBe(120)
            ->and(config('session.encrypt'))->toBeTrue()
            ->and(config('session.http_only'))->toBeTrue()
            ->and(config('session.same_site'))->toBe('lax')
            ->and(config('session.lottery'))->toBe([2, 100]);

        session(['locale' => 'id', 'wizard.step' => 'department']);

        expect(session('locale'))->toBe('id')
            ->and(session('wizard.step'))->toBe('department');
    });
});
