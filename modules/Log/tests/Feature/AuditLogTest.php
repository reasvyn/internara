<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Log\Concerns\InteractsWithActivityLog;
use Modules\Log\Models\AuditLog;
use Modules\Shared\Models\Concerns\HasUuid;
use Modules\User\Models\User;

// Define a local test model to isolate AuditLog tests from other modules.
class AuditLogTestModel extends Model
{
    use HasUuid, InteractsWithActivityLog;

    protected $table = 'audit_log_test_models';
    protected $fillable = ['name', 'value'];
    protected string $activityLogName = 'test';
}

beforeEach(function () {
    Schema::create('audit_log_test_models', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->string('value')->nullable();
        $table->timestamps();
    });
});

test('it records audit log when a model is updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $model = AuditLogTestModel::create(['name' => 'Original', 'value' => 'Old']);
    
    $model->update(['value' => 'New']);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'subject_id' => $model->id,
        'subject_type' => AuditLogTestModel::class,
        'action' => 'updated',
    ]);

    $log = AuditLog::first();
    expect($log->payload['value'])->toBe('New');
});

test('it records audit log when a model is deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $model = AuditLogTestModel::create(['name' => 'To be deleted']);
    $id = $model->id;

    $model->delete();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'subject_id' => $id,
        'subject_type' => AuditLogTestModel::class,
        'action' => 'deleted',
    ]);
});
