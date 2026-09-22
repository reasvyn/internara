<?php

declare(strict_types=1);

use App\Modules\Core\Channels\CustomDatabaseChannel;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Notifications\Notification as BaseNotification;

uses(LazilyRefreshDatabase::class);

describe('TXR2H: custom database channel and sending contract', function (): void {
    test('TXR2H-FR-NOTIF-001: channel constructor injects the contract and sends notifications through it', function (): void {
        $channel = app(CustomDatabaseChannel::class);
        $user = User::factory()->create();

        $notification = new class extends BaseNotification
        {
            public function toCustomDatabase($notifiable): array
            {
                return [
                    'type' => 'contract_test',
                    'title' => 'Contract Test',
                    'message' => 'Sent via contract',
                ];
            }
        };

        $channel->send($user, $notification);

        expect(Notification::where('user_id', $user->id)->where('title', 'Contract Test')->exists())->toBeTrue();
    });

    test('TXR2H-FR-NOTIF-002: channel resolves the user id and skips silently without one; TXR2H-NFR-NOTIF-003: no exception is thrown', function (): void {
        $channel = app(CustomDatabaseChannel::class);

        $mailOnly = new class extends BaseNotification
        {
            public function via($notifiable): array
            {
                return ['mail'];
            }
        };

        // Notification without the structured method is ignored, never persisted.
        $channel->send(User::factory()->create(), $mailOnly);
        expect(Notification::count())->toBe(0);

        // Notifiable with no retrievable id (plain object) is skipped silently.
        $structured = new class extends BaseNotification
        {
            public function via($notifiable): array
            {
                return [CustomDatabaseChannel::class];
            }

            public function toCustomDatabase($notifiable): array
            {
                return ['type' => 'info', 'title' => 'Halo', 'message' => 'Isi'];
            }
        };
        $channel->send(new stdClass, $structured);
        expect(Notification::count())->toBe(0);

        // Unsaved model has no key yet: also skipped without throwing.
        $channel->send(User::factory()->make(), $structured);
        expect(Notification::count())->toBe(0);

        // A resolvable user id flows through to a persisted row.
        $user = User::factory()->create();
        $channel->send($user, $structured);
        expect(Notification::where('user_id', $user->id)->count())->toBe(1);
    });

    test('TXR2H-FR-NOTIF-003: channel maps the five-key payload onto a typed NotificationData row', function (): void {
        $user = User::factory()->create();

        $published = new class extends BaseNotification
        {
            public function via($notifiable): array
            {
                return [CustomDatabaseChannel::class];
            }

            public function toCustomDatabase($notifiable): array
            {
                return [
                    'type' => 'assignment_published',
                    'title' => 'Tugas Baru Diterbitkan',
                    'message' => "Tugas 'Laporan PKL' sekarang tersedia.",
                    'link' => '/student/dashboard',
                    'data' => ['assignment_title' => 'Laporan PKL'],
                ];
            }
        };

        app(CustomDatabaseChannel::class)->send($user, $published);

        $row = Notification::where('user_id', $user->id)->firstOrFail();
        expect($row->type)->toBe('assignment_published')
            ->and($row->title)->toBe('Tugas Baru Diterbitkan')
            ->and($row->message)->toBe("Tugas 'Laporan PKL' sekarang tersedia.")
            ->and($row->link)->toBe('/student/dashboard')
            ->and($row->data)->toBe(['assignment_title' => 'Laporan PKL'])
            ->and($row->is_read)->toBeFalse();
    });

    test('TXR2H-FR-NOTIF-003: channel falls back to safe defaults when type or title is missing', function (): void {
        $user = User::factory()->create();

        $broken = new class extends BaseNotification
        {
            public function via($notifiable): array
            {
                return [CustomDatabaseChannel::class];
            }

            public function toCustomDatabase($notifiable): array
            {
                return ['message' => 'Tanpa kunci wajib'];
            }
        };

        app(CustomDatabaseChannel::class)->send($user, $broken);

        $row = Notification::where('user_id', $user->id)->firstOrFail();
        expect($row->type)->toBe('general')
            ->and($row->title)->toBe('Notification')
            ->and($row->message)->toBe('Tanpa kunci wajib');
        expect(NotificationData::fromArray([
            'userId' => $user->id, 'type' => $row->type, 'title' => $row->title,
        ])->title)->toBe('Notification');
    });
});
