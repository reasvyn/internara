<?php

declare(strict_types=1);

use App\Modules\Core\Support\PiiMasker;

describe('89SRA: PII masking', function (): void {
    test('89SRA-FR-LOG-021: maskArray recurses into nested arrays', function (): void {
        $masked = PiiMasker::maskArray([
            'user' => ['email' => 'student@example.com', 'role' => 'student'],
            'password' => 'secret',
        ]);

        expect($masked)->toBe([
            'user' => ['email' => 'st***@example.com', 'role' => 'student'],
            'password' => '***',
        ]);
    });

    test('89SRA-FR-LOG-022: sensitive keys mask fully, case-insensitively, by substring', function (): void {
        $masked = PiiMasker::maskArray([
            'password' => 'hunter2',
            'PASSWORD' => 'hunter2',
            'user_password' => 'hunter2',
            'token' => 'abc',
            'nickname' => 'jojo',
        ]);

        expect($masked['password'])->toBe('***');
        expect($masked['PASSWORD'])->toBe('***');
        expect($masked['user_password'])->toBe('***');
        expect($masked['token'])->toBe('***');
        expect($masked['nickname'])->toBe('jojo');
    });

    test('89SRA-FR-LOG-023: email keeps first two chars plus domain', function (): void {
        expect(PiiMasker::maskValue('email', 'student@example.com'))->toBe('st***@example.com');
        expect(PiiMasker::maskValue('email', 'ab@example.com'))->toBe('a***@example.com');
        expect(PiiMasker::maskValue('email', 'not-an-email'))->toBe('***');
    });

    test('89SRA-FR-LOG-024: phone keeps only the last four digits', function (): void {
        expect(PiiMasker::maskValue('phone', '081234567890'))->toBe('********7890');
        expect(PiiMasker::maskValue('phone', '123'))->toBe('***');
    });

    test('89SRA-FR-LOG-025: name collapses to first initial plus last name', function (): void {
        expect(PiiMasker::maskValue('name', 'John Smith'))->toBe('J. Smith');
        expect(PiiMasker::maskValue('name', 'Madonna'))->toBe('M******');
        expect(PiiMasker::maskValue('name', ''))->toBe('***');
    });

    test('89SRA-FR-LOG-026: IPv4 keeps the first two octets', function (): void {
        expect(PiiMasker::maskIp('192.168.1.10'))->toBe('192.168.***.***');
        expect(PiiMasker::maskIp(null))->toBeNull();
        expect(PiiMasker::maskIp(''))->toBe('');
        expect(PiiMasker::maskIp('not-an-ip'))->toBe('***.***.***.***');
    });

    test('89SRA-FR-LOG-027: IPv6 keeps the first segment', function (): void {
        expect(PiiMasker::maskIp('2001:db8::1'))->toBe('2001::****');
    });

    test('89SRA-FR-LOG-028: user agent truncates to fifty chars plus ellipsis', function (): void {
        $ua = str_repeat('a', 60);

        expect(PiiMasker::maskUserAgent($ua))->toBe(str_repeat('a', 50).'...');
        expect(PiiMasker::maskUserAgent(null))->toBeNull();
        expect(PiiMasker::maskUserAgent(''))->toBe('');
    });

    test('89SRA-FR-LOG-029: the masked-key list covers the credential family', function (): void {
        foreach (['password', 'password_confirmation', 'secret', 'token', 'api_key',
            'access_token', 'credit_card', 'ssn', 'national_id', 'bank_account'] as $key) {
            expect(PiiMasker::maskValue($key, 'sensitive'))->toBe('***', "key {$key}");
        }

        expect(PiiMasker::maskValue('favorite_color', 'blue'))->toBe('blue');
    });
});
