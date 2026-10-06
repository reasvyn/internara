<?php

declare(strict_types=1);

use App\Modules\Core\Support\Token;

describe('8NZAU: Token support helper', function (): void {
    test('8NZAU-FR-INST-011: generate() mints a 6-char uppercase alphanumeric token by default', function (): void {
        $token = Token::generate();

        expect($token)->toMatch('/^[A-Z0-9]{6}$/')
            ->and(strlen($token))->toBe(6);
    });

    test('8NZAU-FR-INST-011: generate() honors a custom length', function (): void {
        expect(Token::generate(10))->toMatch('/^[A-Z0-9]{10}$/');
    });

    test('8NZAU-FR-INST-011: generate() honors a custom charset', function (): void {
        expect(Token::generate(8, 'ABCDEF'))->toMatch('/^[A-F]{8}$/');
    });

    test('8NZAU-FR-INST-011: generate() rejects invalid length and empty charset', function (): void {
        expect(fn () => Token::generate(0))->toThrow(InvalidArgumentException::class);
        expect(fn () => Token::generate(6, ''))->toThrow(InvalidArgumentException::class);
    });
});
