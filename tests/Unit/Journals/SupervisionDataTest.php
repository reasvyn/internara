<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\MonitoringVisit\Data\CreateVisitData;
use App\Modules\Journals\Domain\SupervisionLog\Data\CreateLogData;
use App\Modules\Journals\Domain\SupervisionLog\Data\CreateSupervisionLogData;
use App\Modules\Journals\Domain\SupervisionLog\Data\ReviewLogData;

describe('2EHSE: CreateVisitData DTO', function (): void {
    test('2EHSE-FR-SUPV-008: fromArray maps the teacher, registration, and visit payload', function (): void {
        $dto = CreateVisitData::fromArray([
            'teacherId' => 'teacher-1',
            'registrationId' => 'reg-1',
            'data' => ['method' => 'site_visit', 'location' => 'PT Maju'],
        ]);

        expect($dto->teacherId)->toBe('teacher-1');
        expect($dto->registrationId)->toBe('reg-1');
        expect($dto->data)->toBe(['method' => 'site_visit', 'location' => 'PT Maju']);
    });

    test('2EHSE-FR-SUPV-008: fromArray accepts snake_case keys', function (): void {
        $dto = CreateVisitData::fromArray([
            'teacher_id' => 'teacher-2',
            'registration_id' => 'reg-2',
            'data' => ['method' => 'virtual_meeting'],
        ]);

        expect($dto->teacherId)->toBe('teacher-2');
        expect($dto->registrationId)->toBe('reg-2');
    });

    test('2EHSE-FR-SUPV-008: fromArray throws when the visit payload is missing', function (): void {
        expect(fn (): CreateVisitData => CreateVisitData::fromArray([
            'teacherId' => 'teacher-1',
            'registrationId' => 'reg-1',
        ]))->toThrow(InvalidArgumentException::class, 'data');
    });

    test('2EHSE-FR-SUPV-008: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['teacherId' => 't', 'registrationId' => 'r', 'data' => ['method' => 'phone_call']];

        expect(CreateVisitData::from($payload)->data)->toBe(['method' => 'phone_call']);

        $source = new class
        {
            public function toArray(): array
            {
                return ['teacherId' => 't2', 'registrationId' => 'r2', 'data' => ['method' => 'site_visit']];
            }
        };

        expect(CreateVisitData::from($source)->teacherId)->toBe('t2');
        expect(fn (): CreateVisitData => CreateVisitData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('2EHSE-FR-SUPV-008: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateVisitData(teacherId: 't', registrationId: 'r', data: ['method' => 'site_visit']);

        expect($dto->toArray())->toBe(['teacherId' => 't', 'registrationId' => 'r', 'data' => ['method' => 'site_visit']]);
        expect($dto->only('data'))->toBe(['data' => ['method' => 'site_visit']]);
        expect($dto->except('data'))->toBe(['teacherId' => 't', 'registrationId' => 'r']);

        $merged = $dto->merge(['data' => ['method' => 'virtual_meeting']]);

        expect($merged->data)->toBe(['method' => 'virtual_meeting']);
        expect($dto->data)->toBe(['method' => 'site_visit']);
    });
});

describe('2EHSE: CreateLogData DTO', function (): void {
    test('2EHSE-FR-SUPV-003: fromArray maps the student, registration, and log payload', function (): void {
        $dto = CreateLogData::fromArray([
            'studentId' => 'student-1',
            'registrationId' => 'reg-1',
            'data' => ['supervisor_id' => 'sup-1', 'topic' => 'Bimbingan'],
        ]);

        expect($dto->studentId)->toBe('student-1');
        expect($dto->registrationId)->toBe('reg-1');
        expect($dto->data)->toBe(['supervisor_id' => 'sup-1', 'topic' => 'Bimbingan']);
    });

    test('2EHSE-FR-SUPV-003: fromArray accepts snake_case keys', function (): void {
        $dto = CreateLogData::fromArray([
            'student_id' => 'student-2',
            'registration_id' => 'reg-2',
            'data' => ['supervisor_id' => 'sup-2'],
        ]);

        expect($dto->studentId)->toBe('student-2');
    });

    test('2EHSE-FR-SUPV-003: fromArray throws when the student is missing', function (): void {
        expect(fn (): CreateLogData => CreateLogData::fromArray([
            'registrationId' => 'reg-1',
            'data' => ['supervisor_id' => 'sup-1'],
        ]))->toThrow(InvalidArgumentException::class, 'studentId');
    });

    test('2EHSE-FR-SUPV-003: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['studentId' => 's', 'registrationId' => 'r', 'data' => ['supervisor_id' => 'x']];

        expect(CreateLogData::from($payload)->studentId)->toBe('s');

        $source = new class
        {
            public function toArray(): array
            {
                return ['studentId' => 's2', 'registrationId' => 'r2', 'data' => ['supervisor_id' => 'y']];
            }
        };

        expect(CreateLogData::from($source)->data)->toBe(['supervisor_id' => 'y']);
        expect(fn (): CreateLogData => CreateLogData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('2EHSE-FR-SUPV-003: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateLogData(studentId: 's', registrationId: 'r', data: ['supervisor_id' => 'x']);

        expect($dto->toArray())->toBe(['studentId' => 's', 'registrationId' => 'r', 'data' => ['supervisor_id' => 'x']]);
        expect($dto->only('studentId'))->toBe(['studentId' => 's']);
        expect($dto->except('studentId'))->toBe(['registrationId' => 'r', 'data' => ['supervisor_id' => 'x']]);

        $merged = $dto->merge(['registrationId' => 'r-baru']);

        expect($merged->registrationId)->toBe('r-baru');
        expect($dto->registrationId)->toBe('r');
    });
});

describe('2EHSE: CreateSupervisionLogData DTO', function (): void {
    test('2EHSE-FR-SUPV-003: fromArray maps the author, registration, and log payload', function (): void {
        $dto = CreateSupervisionLogData::fromArray([
            'userId' => 'teacher-1',
            'registrationId' => 'reg-1',
            'data' => ['topic' => 'Monitoring', 'notes' => 'Berjalan baik'],
        ]);

        expect($dto->userId)->toBe('teacher-1');
        expect($dto->registrationId)->toBe('reg-1');
        expect($dto->data)->toBe(['topic' => 'Monitoring', 'notes' => 'Berjalan baik']);
    });

    test('2EHSE-FR-SUPV-003: fromArray accepts snake_case keys', function (): void {
        $dto = CreateSupervisionLogData::fromArray([
            'user_id' => 'teacher-3',
            'registration_id' => 'reg-3',
            'data' => ['date' => '2026-08-10'],
        ]);

        expect($dto->userId)->toBe('teacher-3');
    });

    test('2EHSE-FR-SUPV-003: fromArray throws when the registration is missing', function (): void {
        expect(fn (): CreateSupervisionLogData => CreateSupervisionLogData::fromArray([
            'userId' => 'teacher-1',
            'data' => ['topic' => 'T'],
        ]))->toThrow(InvalidArgumentException::class, 'registrationId');
    });

    test('2EHSE-FR-SUPV-003: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['userId' => 'u', 'registrationId' => 'r', 'data' => []];

        expect(CreateSupervisionLogData::from($payload)->userId)->toBe('u');

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'registrationId' => 'r2', 'data' => ['topic' => 'T2']];
            }
        };

        expect(CreateSupervisionLogData::from($source)->data)->toBe(['topic' => 'T2']);
        expect(fn (): CreateSupervisionLogData => CreateSupervisionLogData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('2EHSE-FR-SUPV-003: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new CreateSupervisionLogData(userId: 'u', registrationId: 'r', data: ['topic' => 'T']);

        expect($dto->toArray())->toBe(['userId' => 'u', 'registrationId' => 'r', 'data' => ['topic' => 'T']]);
        expect($dto->only('registrationId'))->toBe(['registrationId' => 'r']);
        expect($dto->except('registrationId'))->toBe(['userId' => 'u', 'data' => ['topic' => 'T']]);

        $merged = $dto->merge(['data' => ['topic' => 'T-baru']]);

        expect($merged->data)->toBe(['topic' => 'T-baru']);
        expect($dto->data)->toBe(['topic' => 'T']);
    });
});

describe('2EHSE: ReviewLogData DTO', function (): void {
    test('2EHSE-FR-SUPV-004: fromArray maps the log, reviewer, and feedback', function (): void {
        $dto = ReviewLogData::fromArray([
            'logId' => 'log-1',
            'supervisorId' => 'sup-1',
            'feedback' => 'Dokumentasi perlu dilengkapi.',
        ]);

        expect($dto->logId)->toBe('log-1');
        expect($dto->supervisorId)->toBe('sup-1');
        expect($dto->feedback)->toBe('Dokumentasi perlu dilengkapi.');
    });

    test('2EHSE-FR-SUPV-004: fromArray accepts snake_case keys', function (): void {
        $dto = ReviewLogData::fromArray(['log_id' => 'log-2', 'supervisor_id' => 'sup-2', 'feedback' => 'Bagus']);

        expect($dto->logId)->toBe('log-2');
        expect($dto->supervisorId)->toBe('sup-2');
    });

    test('2EHSE-FR-SUPV-004: fromArray throws when the feedback is missing', function (): void {
        expect(fn (): ReviewLogData => ReviewLogData::fromArray(['logId' => 'log-1', 'supervisorId' => 'sup-1']))
            ->toThrow(InvalidArgumentException::class, 'feedback');
    });

    test('2EHSE-FR-SUPV-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['logId' => 'l', 'supervisorId' => 's', 'feedback' => 'f'];

        expect(ReviewLogData::from($payload)->feedback)->toBe('f');

        $source = new class
        {
            public function toArray(): array
            {
                return ['logId' => 'l2', 'supervisorId' => 's2', 'feedback' => 'f2'];
            }
        };

        expect(ReviewLogData::from($source)->logId)->toBe('l2');
        expect(fn (): ReviewLogData => ReviewLogData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('2EHSE-FR-SUPV-004: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new ReviewLogData(logId: 'l', supervisorId: 's', feedback: 'f');

        expect($dto->toArray())->toBe(['logId' => 'l', 'supervisorId' => 's', 'feedback' => 'f']);
        expect($dto->only('feedback'))->toBe(['feedback' => 'f']);
        expect($dto->except('feedback'))->toBe(['logId' => 'l', 'supervisorId' => 's']);

        $merged = $dto->merge(['feedback' => 'f-baru']);

        expect($merged->feedback)->toBe('f-baru');
        expect($dto->feedback)->toBe('f');
    });
});
