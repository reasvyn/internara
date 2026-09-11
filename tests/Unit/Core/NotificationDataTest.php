<?php

declare(strict_types=1);

use App\Modules\Core\Channels\Data\NotificationData;

describe('TXR2H: NotificationData DTO', function (): void {
    test('TXR2H-FR-NOTIF-005: fromArray maps recipient, type, and title with null optionals', function (): void {
        $dto = NotificationData::fromArray(['userId' => 'user-1', 'type' => 'info', 'title' => 'Selamat datang']);

        expect($dto->userId)->toBe('user-1');
        expect($dto->type)->toBe('info');
        expect($dto->title)->toBe('Selamat datang');
        expect($dto->message)->toBeNull();
        expect($dto->data)->toBeNull();
        expect($dto->link)->toBeNull();
    });

    test('TXR2H-FR-NOTIF-005: fromArray accepts snake_case keys', function (): void {
        $dto = NotificationData::fromArray([
            'user_id' => 'user-2',
            'type' => 'alert',
            'title' => 'T',
            'message' => 'Isi pesan',
            'link' => '/pusat-notifikasi',
        ]);

        expect($dto->userId)->toBe('user-2');
        expect($dto->message)->toBe('Isi pesan');
        expect($dto->link)->toBe('/pusat-notifikasi');
    });

    test('TXR2H-FR-NOTIF-005: fromArray throws when the title is missing', function (): void {
        expect(fn (): NotificationData => NotificationData::fromArray(['userId' => 'u', 'type' => 'info']))
            ->toThrow(InvalidArgumentException::class, 'title');
    });

    test('TXR2H-FR-NOTIF-004: from accepts arrays and arrayables, rejects scalars', function (): void {
        $payload = ['userId' => 'u', 'type' => 'info', 'title' => 'T'];

        expect(NotificationData::from($payload)->type)->toBe('info');

        $source = new class
        {
            public function toArray(): array
            {
                return ['userId' => 'u2', 'type' => 'info', 'title' => 'T2', 'data' => ['k' => 'v']];
            }
        };

        $dto = NotificationData::from($source);

        expect($dto->userId)->toBe('u2');
        expect($dto->data)->toBe(['k' => 'v']);
        expect(fn (): NotificationData => NotificationData::from(42))->toThrow(InvalidArgumentException::class);
    });

    test('TXR2H-FR-NOTIF-008: toArray, only, except, and merge shape the payload', function (): void {
        $dto = new NotificationData(userId: 'u', type: 'info', title: 'T', message: 'M');

        expect($dto->toArray())->toBe([
            'userId' => 'u',
            'type' => 'info',
            'title' => 'T',
            'message' => 'M',
            'data' => null,
            'link' => null,
        ]);
        expect($dto->only('title', 'message'))->toBe(['title' => 'T', 'message' => 'M']);
        expect($dto->except('message', 'data', 'link'))->toBe(['userId' => 'u', 'type' => 'info', 'title' => 'T']);

        $merged = $dto->merge(['link' => '/x']);

        expect($merged->link)->toBe('/x');
        expect($dto->link)->toBeNull();
    });
});
