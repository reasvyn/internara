<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Log\Models\AuditLog;
use Modules\Setting\Models\Setting;
use Modules\User\Models\User;

uses(RefreshDatabase::class);

test('it records audit log when a setting is updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $setting = Setting::create([
        'key' => 'system_phase',
        'value' => 'registration',
        'type' => 'string',
    ]);

    $setting->update(['value' => 'operation']);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'subject_id' => 'system_phase',
        'subject_type' => Setting::class,
        'action' => 'updated',
    ]);

    $log = AuditLog::where('subject_id', 'system_phase')->first();
    expect($log->payload['value'])->toBe('operation');
});
