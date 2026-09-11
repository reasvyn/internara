<?php

declare(strict_types=1);

use App\Modules\Core\Data\AuditCheck;
use App\Modules\Core\Data\AuditReport;
use App\Modules\Core\Enums\AuditCategory;
use App\Modules\Core\Enums\AuditStatus;

describe('8NZAU: AuditCheck DTO', function (): void {
    test('8NZAU-FR-INST-004: fromArray maps the categorized check with empty param defaults', function (): void {
        $dto = AuditCheck::fromArray([
            'category' => AuditCategory::REQUIREMENTS,
            'nameKey' => 'setup.audit.php_version',
            'status' => AuditStatus::PASS,
            'messageKey' => 'setup.audit.php_version_ok',
        ]);

        expect($dto->category)->toBe(AuditCategory::REQUIREMENTS);
        expect($dto->nameKey)->toBe('setup.audit.php_version');
        expect($dto->status)->toBe(AuditStatus::PASS);
        expect($dto->messageKey)->toBe('setup.audit.php_version_ok');
        expect($dto->nameParams)->toBe([]);
        expect($dto->messageParams)->toBe([]);
    });

    test('8NZAU-FR-INST-004: fromArray accepts snake_case keys', function (): void {
        $dto = AuditCheck::fromArray([
            'category' => AuditCategory::DATABASE,
            'name_key' => 'setup.audit.db',
            'status' => AuditStatus::WARN,
            'message_key' => 'setup.audit.db_slow',
            'name_params' => ['driver' => 'sqlite'],
        ]);

        expect($dto->category)->toBe(AuditCategory::DATABASE);
        expect($dto->nameKey)->toBe('setup.audit.db');
        expect($dto->nameParams)->toBe(['driver' => 'sqlite']);
    });

    test('8NZAU-FR-INST-004: fromArray throws when the status is missing', function (): void {
        expect(fn (): AuditCheck => AuditCheck::fromArray([
            'category' => AuditCategory::PERMISSIONS,
            'nameKey' => 'k',
            'messageKey' => 'm',
        ]))->toThrow(InvalidArgumentException::class, 'status');
    });

    test('8NZAU-FR-INST-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = [
            'category' => AuditCategory::TERMINAL,
            'nameKey' => 'k',
            'status' => AuditStatus::PASS,
            'messageKey' => 'm',
        ];

        expect(AuditCheck::from($payload)->status)->toBe(AuditStatus::PASS);

        $source = new class
        {
            public function toArray(): array
            {
                return [
                    'category' => AuditCategory::RECOMMENDATIONS,
                    'nameKey' => 'k2',
                    'status' => AuditStatus::FAIL,
                    'messageKey' => 'm2',
                ];
            }
        };

        expect(AuditCheck::from($source)->status)->toBe(AuditStatus::FAIL);
        expect(fn (): AuditCheck => AuditCheck::from('x'))->toThrow(InvalidArgumentException::class);
    });

    test('8NZAU-FR-INST-004: toArray keeps enum instances while only, except, and merge shape the payload', function (): void {
        $dto = new AuditCheck(
            category: AuditCategory::REQUIREMENTS,
            nameKey: 'k',
            status: AuditStatus::PASS,
            messageKey: 'm',
        );

        expect($dto->toArray()['status'])->toBe(AuditStatus::PASS);
        expect($dto->only('category', 'status'))->toBe([
            'category' => AuditCategory::REQUIREMENTS,
            'status' => AuditStatus::PASS,
        ]);
        expect(array_key_exists('messageKey', $dto->except('status')))->toBeTrue();

        $merged = $dto->merge(['status' => AuditStatus::WARN]);

        expect($merged->status)->toBe(AuditStatus::WARN);
        expect($dto->status)->toBe(AuditStatus::PASS);
    });
});

describe('8NZAU: AuditReport DTO', function (): void {
    test('8NZAU-FR-INST-004: fromArray defaults to an empty check list', function (): void {
        $dto = AuditReport::fromArray([]);

        expect($dto->checks)->toBe([]);
        expect($dto->passed())->toBeTrue();
    });

    test('8NZAU-FR-INST-004: passed fails when any check fails', function (): void {
        $pass = new AuditCheck(AuditCategory::REQUIREMENTS, 'k1', AuditStatus::PASS, 'm1');
        $fail = new AuditCheck(AuditCategory::DATABASE, 'k2', AuditStatus::FAIL, 'm2');
        $warn = new AuditCheck(AuditCategory::PERMISSIONS, 'k3', AuditStatus::WARN, 'm3');

        expect((new AuditReport([$pass, $warn]))->passed())->toBeTrue();
        expect((new AuditReport([$pass, $fail]))->passed())->toBeFalse();
    });

    test('8NZAU-FR-INST-004: forCategory filters checks by category', function (): void {
        $dbCheck = new AuditCheck(AuditCategory::DATABASE, 'k1', AuditStatus::PASS, 'm1');
        $reqCheck = new AuditCheck(AuditCategory::REQUIREMENTS, 'k2', AuditStatus::PASS, 'm2');
        $report = new AuditReport([$dbCheck, $reqCheck]);

        expect($report->forCategory(AuditCategory::DATABASE))->toBe([$dbCheck]);
        expect($report->forCategory(AuditCategory::TERMINAL))->toBe([]);
    });

    test('8NZAU-FR-INST-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        $check = new AuditCheck(AuditCategory::REQUIREMENTS, 'k', AuditStatus::PASS, 'm');

        expect(AuditReport::from(['checks' => [$check]])->checks)->toBe([$check]);

        $source = new class
        {
            public function toArray(): array
            {
                return ['checks' => []];
            }
        };

        expect(AuditReport::from($source)->passed())->toBeTrue();
        expect(fn (): AuditReport => AuditReport::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('8NZAU-FR-INST-004: toArray nests checks while only and except shape the payload', function (): void {
        $check = new AuditCheck(AuditCategory::REQUIREMENTS, 'k', AuditStatus::PASS, 'm');
        $report = new AuditReport([$check]);

        expect($report->toArray())->toBe(['checks' => [[
            'category' => AuditCategory::REQUIREMENTS,
            'nameKey' => 'k',
            'status' => AuditStatus::PASS,
            'messageKey' => 'm',
            'nameParams' => [],
            'messageParams' => [],
        ]]]);
        expect($report->only('checks'))->toBe(['checks' => $report->toArray()['checks']]);
        expect($report->except('checks'))->toBe([]);
    });
});
