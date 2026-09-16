<?php

declare(strict_types=1);

use App\Modules\Core\Support\CsvHandler;
use App\Modules\User\Domain\UserManagement\Livewire\UserManager;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function makeUserCsvAdmin(object $test): void
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $test->actingAs($admin);
}

function userCsvFile(string $content): UploadedFile
{
    return UploadedFile::fake()->createWithContent('users.csv', $content);
}

function captureUserCsv(mixed $response): string
{
    ob_start();

    try {
        $response->sendContent();
    } finally {
        $captured = ob_get_clean();
    }

    return is_string($captured) ? $captured : '';
}

describe('O2KCR: user CSV import and export', function (): void {
    test('O2KCR-FR-CSV-016: user import creates accounts from full_name email and phone columns', function (): void {
        makeUserCsvAdmin($this);

        $content = "full_name,email,phone\nBudi Santoso,budi@example.test,0800111222\nSiti Aminah,siti@example.test,0800333444\n";

        Livewire::test(UserManager::class)
            ->set('importFile', userCsvFile($content))
            ->assertSet('importFile', null);

        $budi = User::where('email', 'budi@example.test')->firstOrFail();

        expect($budi->name)->toBe('Budi Santoso');
        expect($budi->profile->phone)->toBe('0800111222');
        expect(User::where('email', 'siti@example.test')->exists())->toBeTrue();
    });

    test('O2KCR-UC-CSV-001: mixed user file mints credentials for valid rows only (covers O2KCR-FR-CSV-017, O2KCR-FR-CSV-018)', function (): void {
        makeUserCsvAdmin($this);
        User::factory()->create(['email' => 'existing@example.test']);

        $content = "full_name,email,phone\n"
            ."Budi Santoso,budi@example.test,0800111222\n"
            ."Existing Person,existing@example.test,0800000000\n"
            ."Broken Row,not-an-email,0800999888\n"
            ."Siti Aminah,siti@example.test,0800333444\n";

        Livewire::test(UserManager::class)->set('importFile', userCsvFile($content));

        $budi = User::where('email', 'budi@example.test')->firstOrFail();
        $siti = User::where('email', 'siti@example.test')->firstOrFail();

        expect(User::where('email', 'existing@example.test')->count())->toBe(1);
        expect(User::where('email', 'not-an-email')->exists())->toBeFalse();

        foreach ([$budi, $siti] as $user) {
            expect($user->username)->not->toBe('');
            expect($user->password)->toStartWith('$2y$');
            expect(Hash::check('password', $user->password))->toBeFalse();
        }

        expect($budi->password)->not->toBe($siti->password);
        expect($budi->username)->not->toBe($siti->username);
    });

    test('O2KCR-FR-CSV-019: user export carries identity columns and never secrets (covers O2KCR-NFR-CSV-004)', function (): void {
        makeUserCsvAdmin($this);
        $student = User::factory()->create(['name' => 'Budi Santoso']);
        $student->assignRole('student');

        $body = captureUserCsv((new UserManager)->export(new CsvHandler));
        $rows = array_map('str_getcsv', explode("\n", trim($body)));

        expect($rows[0])->toContain('Username');
        expect($rows[0])->not->toContain('password');
        expect($rows[0])->not->toContain('token');

        $usernames = array_column(array_slice($rows, 1), 2);

        expect($usernames)->toContain($student->username);

        foreach ($rows as $row) {
            expect(implode(',', $row))->not->toContain($student->password);
        }

        expect($body)->toContain('Budi Santoso');
    });

    test('O2KCR-FR-CSV-020: user export filenames follow the trio', function (): void {
        makeUserCsvAdmin($this);
        $student = User::factory()->create();
        $student->assignRole('student');

        expect((new UserManager)->export(new CsvHandler)->headers->get('Content-Disposition'))
            ->toBe('attachment; filename="users.csv"');

        $manager = new UserManager;
        $manager->selectedIds = [$student->id];

        expect($manager->exportSelected(new CsvHandler)->headers->get('Content-Disposition'))
            ->toBe('attachment; filename="users-selected.csv"');

        expect((new UserManager)->downloadTemplate(new CsvHandler)->headers->get('Content-Disposition'))
            ->toBe('attachment; filename="users-template.csv"');
    });

    test('O2KCR-NFR-CSV-015: user export headers translate across locales (covers O2KCR-NFR-CSV-016)', function (): void {
        makeUserCsvAdmin($this);
        User::factory()->create();

        app()->setLocale('id');
        $bodyId = captureUserCsv((new UserManager)->export(new CsvHandler));

        app()->setLocale('en');
        $bodyEn = captureUserCsv((new UserManager)->export(new CsvHandler));

        expect($bodyId)->toContain('Nama Lengkap');
        expect($bodyId)->not->toContain('Full Name');
        expect($bodyEn)->toContain('Full Name');
    });

    test('O2KCR-UC-CSV-012: user template downloads headers plus an example row (covers O2KCR-FR-CSV-020)', function (): void {
        makeUserCsvAdmin($this);

        $rows = array_map('str_getcsv', explode("\n", trim(captureUserCsv((new UserManager)->downloadTemplate(new CsvHandler)))));

        expect($rows)->toHaveCount(2);
        expect($rows[0])->toHaveCount(3);
    });
});
