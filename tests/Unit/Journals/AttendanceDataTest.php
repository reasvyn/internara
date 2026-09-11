<?php

declare(strict_types=1);

use App\Modules\Journals\Domain\AbsenceRequest\Data\ProcessAbsenceData;
use App\Modules\Journals\Domain\AbsenceRequest\Data\SubmitAbsenceData;
use App\Modules\Journals\Domain\AbsenceRequest\Enums\AbsenceRequestStatus;
use App\Modules\Journals\Domain\Attendance\Data\ClockInData;
use App\Modules\Journals\Domain\Attendance\Data\ClockOutData;

describe('1KSWL: ClockInData DTO', function (): void {
    test('1KSWL-FR-DAILY-007: fromArray maps the student with empty metadata defaults', function (): void {
        $dto = ClockInData::fromArray(['userId' => 'user-1']);

        expect($dto->userId)->toBe('user-1');
        expect($dto->data)->toBe([]);
        expect($dto->requestIp)->toBeNull();
    });

    test('1KSWL-FR-DAILY-007: fromArray accepts snake_case keys with GPS metadata', function (): void {
        $dto = ClockInData::fromArray([
            'user_id' => 'user-2',
            'data' => ['latitude' => -6.2, 'longitude' => 106.8],
            'request_ip' => '127.0.0.1',
        ]);

        expect($dto->userId)->toBe('user-2');
        expect($dto->data)->toBe(['latitude' => -6.2, 'longitude' => 106.8]);
        expect($dto->requestIp)->toBe('127.0.0.1');
    });

    test('1KSWL-FR-DAILY-007: fromArray throws when the student is missing', function (): void {
        expect(fn (): ClockInData => ClockInData::fromArray(['data' => []]))
            ->toThrow(InvalidArgumentException::class, 'userId');
    });

    test('1KSWL-FR-DAILY-007: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(ClockInData::from(['userId' => 'u'])->data)->toBe([]);

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'requestIp' => '10.0.0.1'];
            }
        };

        expect(ClockInData::from($source)->requestIp)->toBe('10.0.0.1');
        expect(fn (): ClockInData => ClockInData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('1KSWL-FR-DAILY-007: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new ClockInData(userId: 'u', data: ['latitude' => 1.0]);

        expect($dto->toArray())->toBe(['userId' => 'u', 'data' => ['latitude' => 1.0], 'requestIp' => null]);
        expect($dto->only('userId'))->toBe(['userId' => 'u']);
        expect($dto->except('requestIp'))->toBe(['userId' => 'u', 'data' => ['latitude' => 1.0]]);

        $merged = $dto->merge(['requestIp' => '127.0.0.1']);

        expect($merged->requestIp)->toBe('127.0.0.1');
        expect($dto->requestIp)->toBeNull();
    });
});

describe('1KSWL: ClockOutData DTO', function (): void {
    test('1KSWL-FR-DAILY-008: fromArray maps the student and clock payload', function (): void {
        $dto = ClockOutData::fromArray(['userId' => 'user-1', 'data' => ['latitude' => -6.2]]);

        expect($dto->userId)->toBe('user-1');
        expect($dto->data)->toBe(['latitude' => -6.2]);
        expect($dto->requestIp)->toBeNull();
    });

    test('1KSWL-FR-DAILY-008: fromArray accepts snake_case keys', function (): void {
        $dto = ClockOutData::fromArray(['user_id' => 'user-3', 'data' => [], 'request_ip' => '127.0.0.1']);

        expect($dto->userId)->toBe('user-3');
        expect($dto->requestIp)->toBe('127.0.0.1');
    });

    test('1KSWL-FR-DAILY-008: fromArray throws when the clock payload is missing', function (): void {
        expect(fn (): ClockOutData => ClockOutData::fromArray(['userId' => 'user-1']))
            ->toThrow(InvalidArgumentException::class, 'data');
    });

    test('1KSWL-FR-DAILY-008: from accepts arrays and arrayables, rejects scalars', function (): void {
        expect(ClockOutData::from(['userId' => 'u', 'data' => []])->userId)->toBe('u');

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'data' => ['longitude' => 106.0]];
            }
        };

        expect(ClockOutData::from($source)->data)->toBe(['longitude' => 106.0]);
        expect(fn (): ClockOutData => ClockOutData::from(null))->toThrow(InvalidArgumentException::class);
    });

    test('1KSWL-FR-DAILY-008: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new ClockOutData(userId: 'u', data: []);

        expect($dto->toArray())->toBe(['userId' => 'u', 'data' => [], 'requestIp' => null]);
        expect($dto->only('data'))->toBe(['data' => []]);
        expect($dto->except('data'))->toBe(['userId' => 'u', 'requestIp' => null]);

        $merged = $dto->merge(['data' => ['latitude' => 2.0]]);

        expect($merged->data)->toBe(['latitude' => 2.0]);
        expect($dto->data)->toBe([]);
    });
});

describe('1KSWL: SubmitAbsenceData DTO', function (): void {
    test('1KSWL-FR-DAILY-012: fromArray maps the student, registration, and reason payload', function (): void {
        $dto = SubmitAbsenceData::fromArray([
            'userId' => 'user-1',
            'registrationId' => 'reg-1',
            'data' => ['reason_type' => 'sick', 'reason_description' => 'Demam'],
        ]);

        expect($dto->userId)->toBe('user-1');
        expect($dto->registrationId)->toBe('reg-1');
        expect($dto->data)->toBe(['reason_type' => 'sick', 'reason_description' => 'Demam']);
    });

    test('1KSWL-FR-DAILY-012: fromArray accepts snake_case keys', function (): void {
        $dto = SubmitAbsenceData::fromArray([
            'user_id' => 'user-4',
            'registration_id' => 'reg-4',
            'data' => ['reason_type' => 'permit'],
        ]);

        expect($dto->userId)->toBe('user-4');
        expect($dto->registrationId)->toBe('reg-4');
    });

    test('1KSWL-FR-DAILY-012: fromArray throws when the reason payload is missing', function (): void {
        expect(fn (): SubmitAbsenceData => SubmitAbsenceData::fromArray(['userId' => 'u', 'registrationId' => 'r']))
            ->toThrow(InvalidArgumentException::class, 'data');
    });

    test('1KSWL-FR-DAILY-012: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['userId' => 'u', 'registrationId' => 'r', 'data' => ['reason_type' => 'sick']];

        expect(SubmitAbsenceData::from($payload)->data)->toBe(['reason_type' => 'sick']);

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'registrationId' => 'r2', 'data' => ['reason_type' => 'emergency']];
            }
        };

        expect(SubmitAbsenceData::from($source)->registrationId)->toBe('r2');
        expect(fn (): SubmitAbsenceData => SubmitAbsenceData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('1KSWL-FR-DAILY-012: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new SubmitAbsenceData(userId: 'u', registrationId: 'r', data: ['reason_type' => 'sick']);

        expect($dto->toArray())->toBe(['userId' => 'u', 'registrationId' => 'r', 'data' => ['reason_type' => 'sick']]);
        expect($dto->only('data'))->toBe(['data' => ['reason_type' => 'sick']]);
        expect($dto->except('data'))->toBe(['userId' => 'u', 'registrationId' => 'r']);

        $merged = $dto->merge(['data' => ['reason_type' => 'permit']]);

        expect($merged->data)->toBe(['reason_type' => 'permit']);
        expect($dto->data)->toBe(['reason_type' => 'sick']);
    });
});

describe('1KSWL: ProcessAbsenceData DTO', function (): void {
    test('1KSWL-FR-DAILY-013: fromArray maps the absence, processor, and verdict', function (): void {
        $dto = ProcessAbsenceData::fromArray([
            'absenceId' => 'abs-1',
            'processorId' => 'teacher-1',
            'status' => AbsenceRequestStatus::APPROVED,
        ]);

        expect($dto->absenceId)->toBe('abs-1');
        expect($dto->processorId)->toBe('teacher-1');
        expect($dto->status)->toBe(AbsenceRequestStatus::APPROVED);
        expect($dto->notes)->toBeNull();
    });

    test('1KSWL-FR-DAILY-013: fromArray accepts snake_case keys with notes', function (): void {
        $dto = ProcessAbsenceData::fromArray([
            'absence_id' => 'abs-2',
            'processor_id' => 'teacher-2',
            'status' => AbsenceRequestStatus::REJECTED,
            'notes' => 'Bukti tidak valid.',
        ]);

        expect($dto->absenceId)->toBe('abs-2');
        expect($dto->status)->toBe(AbsenceRequestStatus::REJECTED);
        expect($dto->notes)->toBe('Bukti tidak valid.');
    });

    test('1KSWL-FR-DAILY-013: fromArray throws when the verdict is missing', function (): void {
        expect(fn (): ProcessAbsenceData => ProcessAbsenceData::fromArray([
            'absenceId' => 'abs-1',
            'processorId' => 'teacher-1',
        ]))->toThrow(InvalidArgumentException::class, 'status');
    });

    test('1KSWL-FR-DAILY-013: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['absenceId' => 'a', 'processorId' => 'p', 'status' => AbsenceRequestStatus::APPROVED];

        expect(ProcessAbsenceData::from($payload)->status)->toBe(AbsenceRequestStatus::APPROVED);

        $source = new class
        {
            public function toArray(): array
            {
                return ['absenceId' => 'a2', 'processorId' => 'p2', 'status' => AbsenceRequestStatus::REJECTED];
            }
        };

        expect(ProcessAbsenceData::from($source)->status)->toBe(AbsenceRequestStatus::REJECTED);
        expect(fn (): ProcessAbsenceData => ProcessAbsenceData::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('1KSWL-FR-DAILY-013: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new ProcessAbsenceData(absenceId: 'a', processorId: 'p', status: AbsenceRequestStatus::APPROVED);

        expect($dto->toArray()['status'])->toBe(AbsenceRequestStatus::APPROVED);
        expect($dto->only('status'))->toBe(['status' => AbsenceRequestStatus::APPROVED]);
        expect(array_key_exists('notes', $dto->except('status')))->toBeTrue();

        $merged = $dto->merge(['status' => AbsenceRequestStatus::REJECTED]);

        expect($merged->status)->toBe(AbsenceRequestStatus::REJECTED);
        expect($dto->status)->toBe(AbsenceRequestStatus::APPROVED);
    });
});
