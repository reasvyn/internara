<?php

declare(strict_types=1);

use App\Modules\Core\Events\BaseEvent;
use App\Modules\Core\Services\SmartLogger;

describe('89SRA: SmartLogger event integration and translation', function (): void {
    test('89SRA-FR-LOG-013 + 89SRA-FR-LOG-016: string and object events resolve the same translatable name', function (): void {
        app()->setLocale('en');
        $captured = captureLogs();

        SmartLogger::info('string-event probe')->event('login_success')->systemOnly()->save();

        $objectEvent = new class extends BaseEvent
        {
            public function eventName(): string
            {
                return 'login_success';
            }
        };
        SmartLogger::info('object-event probe')->event($objectEvent)->systemOnly()->save();

        $stringRecord = $captured->firstWhere('message', 'string-event probe');
        $objectRecord = $captured->firstWhere('message', 'object-event probe');

        expect($stringRecord->context['event'])->toBe('login_success')
            ->and($objectRecord->context['event'])->toBe('login_success')
            ->and($stringRecord->context['event_description'])->toBe($objectRecord->context['event_description'])
            ->and($stringRecord->context['event_description'])->toContain('authenticated');
    });

    test('89SRA-FR-LOG-017: current-locale lookup resolves the Indonesian description', function (): void {
        app()->setLocale('id');
        $captured = captureLogs();

        SmartLogger::info('locale-id probe')->event('login_success')->systemOnly()->save();

        $record = $captured->firstWhere('message', 'locale-id probe');

        expect($record->context['event_description'])->toBe('Pengguna berhasil masuk ke dalam sistem.');
    });

    test('89SRA-FR-LOG-018 + 89SRA-FR-LOG-019: mirror locale ships under contracted injection keys', function (): void {
        app()->setLocale('en');
        $captured = captureLogs();

        SmartLogger::info('mirror-locale probe')->event('login_success')->systemOnly()->save();

        $record = $captured->firstWhere('message', 'mirror-locale probe');

        expect($record->context['event_description'])->toBe('User has successfully authenticated into the system.')
            ->and($record->context['event_description_id'])->toBe('Pengguna berhasil masuk ke dalam sistem.');
    });

    test('89SRA-FR-LOG-020: missing translation keys never throw and inject nothing', function (): void {
        $captured = captureLogs();

        SmartLogger::info('untranslated probe')->event('no_such_event_xyz_89sra')->systemOnly()->save();

        $record = $captured->firstWhere('message', 'untranslated probe');

        expect($record)->not->toBeNull()
            ->and($record->context)->not->toHaveKey('event_description')
            ->and($record->context)->not->toHaveKey('event_description_id')
            ->and($record->context)->not->toHaveKey('event_description_en');
    });

    test('89SRA-FR-LOG-015: manual payload wins over the event toPayload merge', function (): void {
        $captured = captureLogs();

        $event = new class extends BaseEvent
        {
            public int $entries = 5;

            public string $source = 'event';

            public function eventName(): string
            {
                return 'login_success';
            }
        };

        SmartLogger::info('precedence probe')->event($event)->withPayload(['entries' => 7])->systemOnly()->save();

        $record = $captured->firstWhere('message', 'precedence probe');

        expect($record->context['payload'])->toMatchArray(['entries' => 7, 'source' => 'event']);
    });
});
