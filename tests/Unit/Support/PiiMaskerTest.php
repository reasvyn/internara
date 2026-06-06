<?php

declare(strict_types=1);

use App\Support\PiiMasker;

describe('maskArray', function () {
    it('fully masks sensitive keys', function () {
        $result = PiiMasker::maskArray([
            'password' => 'secret123',
            'api_token' => 'tok_abc',
        ]);

        expect($result['password'])->toBe('***');
        expect($result['api_token'])->toBe('***');
    });

    it('partially masks email', function () {
        $result = PiiMasker::maskArray(['email' => 'john@example.com']);

        expect($result['email'])->toBe('jo***@example.com');
    });

    it('partially masks phone', function () {
        $result = PiiMasker::maskArray(['phone' => '08123456789']);

        expect($result['phone'])->toBe('*******6789');
    });

    it('partially masks name', function () {
        $result = PiiMasker::maskArray(['name' => 'John Doe']);

        expect($result['name'])->toBe('J. Doe');
    });

    it('recursively masks nested arrays', function () {
        $result = PiiMasker::maskArray([
            'user' => [
                'password' => 'secret',
                'email' => 'a@b.com',
            ],
        ]);

        expect($result['user']['password'])->toBe('***');
        expect($result['user']['email'])->toBe('a***@b.com');
    });

    it('leaves non-sensitive values unchanged', function () {
        $result = PiiMasker::maskArray([
            'name' => 'John',
            'title' => 'Engineer',
        ]);

        expect($result['title'])->toBe('Engineer');
    });

    it('detects key by substring', function () {
        $result = PiiMasker::maskArray(['old_password' => 'secret']);

        expect($result['old_password'])->toBe('***');
    });

    it('masks additional pii keys', function () {
        $result = PiiMasker::maskArray([
            'birth_date' => '1990-01-01',
            'dob' => '1990-01-01',
            'address' => '123 Main St',
            'passport' => 'AB123456',
            'driver_license' => 'DL123456',
            'drivers_license' => 'DL123456',
            'security_question' => 'Mother maiden name',
            'security_answer' => 'Smith',
            'bank_account' => '1234567890',
            'account_number' => '1234567890',
            'routing_number' => '123456789',
            'health_insurance' => 'HI-12345',
            'medical_record' => 'MR-98765',
        ]);

        expect($result['birth_date'])->toBe('***');
        expect($result['dob'])->toBe('***');
        expect($result['address'])->toBe('***');
        expect($result['passport'])->toBe('***');
        expect($result['driver_license'])->toBe('***');
        expect($result['drivers_license'])->toBe('***');
        expect($result['security_question'])->toBe('***');
        expect($result['security_answer'])->toBe('***');
        expect($result['bank_account'])->toBe('***');
        expect($result['account_number'])->toBe('***');
        expect($result['routing_number'])->toBe('***');
        expect($result['health_insurance'])->toBe('***');
        expect($result['medical_record'])->toBe('***');
    });

    it('handles null values without error', function () {
        $result = PiiMasker::maskArray([
            'password' => null,
            'email' => null,
            'name' => null,
            'regular_key' => null,
        ]);

        expect($result['password'])->toBe('***');
        expect($result['email'])->toBe('***');
        expect($result['name'])->toBe('***');
        expect($result['regular_key'])->toBeNull();
    });

    it('handles integer keys', function () {
        $result = PiiMasker::maskArray([
            'password',
            'email' => 'test@example.com',
        ]);

        expect($result[0])->toBe('password');
        expect($result['email'])->toBe('te***@example.com');
    });

    it('handles empty array', function () {
        expect(PiiMasker::maskArray([]))->toBe([]);
    });

    it('handles mixed nested structures', function () {
        $result = PiiMasker::maskArray([
            'user' => [
                'profile' => [
                    'password' => 'secret',
                    'email' => 'user@example.com',
                ],
                'metadata' => 'visible',
            ],
        ]);

        expect($result['user']['profile']['password'])->toBe('***');
        expect($result['user']['profile']['email'])->toBe('us***@example.com');
        expect($result['user']['metadata'])->toBe('visible');
    });
});

describe('maskValue', function () {
    it('fully masks sensitive keys', function () {
        expect(PiiMasker::maskValue('password', 'secret'))->toBe('***');
    });

    it('partially masks by key', function () {
        expect(PiiMasker::maskValue('email', 'test@example.com'))->toContain('@');
    });

    it('returns value for non-sensitive keys', function () {
        expect(PiiMasker::maskValue('username', 'john'))->toBe('john');
    });
});

describe('maskEmail', function () {
    it('masks local part of email', function () {
        expect(PiiMasker::maskValue('email', 'john@example.com'))->toBe('jo***@example.com');
    });

    it('handles short local part', function () {
        expect(PiiMasker::maskValue('email', 'a@b.com'))->toBe('a***@b.com');
    });

    it('returns *** for invalid email', function () {
        expect(PiiMasker::maskValue('email', 'notanemail'))->toBe('***');
    });
});

describe('maskPhone', function () {
    it('shows last 4 digits', function () {
        expect(PiiMasker::maskValue('phone', '+628123456789'))->toMatch('/\*{7}6789$/');
    });

    it('returns *** for short values', function () {
        expect(PiiMasker::maskValue('phone', '123'))->toBe('***');
    });
});

describe('maskName', function () {
    it('shows initial and last name', function () {
        expect(PiiMasker::maskValue('name', 'John Doe'))->toBe('J. Doe');
    });

    it('masks single word names', function () {
        $result = PiiMasker::maskValue('name', 'Madonna');

        expect($result)->toMatch('/^M\*{6}$/');
    });

    it('masks empty name', function () {
        $result = PiiMasker::maskValue('name', '');

        expect($result)->toBe('***');
    });

    it('masks multi word name with middle name', function () {
        expect(PiiMasker::maskValue('name', 'John Michael Doe'))->toBe('J. Doe');
    });
});

describe('maskIp', function () {
    it('masks IPv4', function () {
        expect(PiiMasker::maskIp('192.168.1.1'))->toBe('192.168.***.***');
    });

    it('masks IPv6', function () {
        expect(PiiMasker::maskIp('2001:db8::1'))->toContain('****');
    });

    it('returns null for null input', function () {
        expect(PiiMasker::maskIp(null))->toBeNull();
    });

    it('returns empty string for empty input', function () {
        expect(PiiMasker::maskIp(''))->toBe('');
    });

    it('masks ipv6 with fewer segments', function () {
        $result = PiiMasker::maskIp('::1');

        expect($result)->toContain('****');
    });

    it('masks malformed ip', function () {
        expect(PiiMasker::maskIp('not-an-ip'))->toBe('***.***.***.***');
    });
});

describe('maskUserAgent', function () {
    it('truncates long user agents', function () {
        $long = str_repeat('a', 100);

        $result = PiiMasker::maskUserAgent($long);

        expect($result)->toEndWith('...');
        expect(strlen($result))->toBe(53);
    });

    it('returns null for null input', function () {
        expect(PiiMasker::maskUserAgent(null))->toBeNull();
    });

    it('returns empty string for empty input', function () {
        expect(PiiMasker::maskUserAgent(''))->toBe('');
    });

    it('does not truncate short user agents', function () {
        $result = PiiMasker::maskUserAgent('Mozilla/5.0');

        expect($result)->toBe('Mozilla/5.0...');
    });
});
