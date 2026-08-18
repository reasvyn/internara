<?php

declare(strict_types=1);

use App\Document\Handbook\Actions\AcknowledgeHandbookAction;
use App\Document\Handbook\Enums\HandbookAudience;
use App\Document\Models\Document;
use App\User\Dashboard\Actions\ReadStudentDashboardAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('CKKZC-FR-DD2: handbookReadCount counts acknowledged handbook documents', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $handbook = Document::create([
        'type' => 'handbook',
        'slug' => 'student-handbook-'.uniqid(),
        'title' => 'Student Handbook',
        'version' => 1,
        'is_active' => true,
        'metadata' => ['target_audience' => HandbookAudience::ALL->value],
        'created_by' => User::factory()->create()->id,
    ]);

    app(AcknowledgeHandbookAction::class)->execute($handbook, $student);

    $stats = app(ReadStudentDashboardAction::class)->execute($student->id);

    expect($stats['handbookTotalCount'])->toBe(1);
    expect($stats['handbookReadCount'])->toBe(1);
});
