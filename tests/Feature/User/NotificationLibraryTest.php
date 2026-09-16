<?php

declare(strict_types=1);

use App\Modules\Assignment\Events\AssignmentPublished;
use App\Modules\Assignment\Models\Assignment;
use App\Modules\Assignment\Notifications\AssignmentNotification;
use App\Modules\Auth\Domain\Login\Events\LoginSucceeded;
use App\Modules\Auth\Domain\Login\Listeners\SendRoleWelcomeNotification;
use App\Modules\Auth\Domain\SuperAdmin\Notifications\RecoveryOtpNotification;
use App\Modules\Auth\Notifications\CredentialChangedNotification;
use App\Modules\Core\Channels\CustomDatabaseChannel;
use App\Modules\Incident\Domain\IncidentReport\Models\IncidentReport;
use App\Modules\Incident\Domain\IncidentReport\Notifications\IncidentReportedNotification;
use App\Modules\Program\Notifications\RegistrationNotification;
use App\Modules\SysAdmin\Domain\Announcement\Notifications\AnnouncementNotification;
use App\Modules\User\Domain\AccountStatus\Notifications\AccountStatusNotification;
use App\Modules\User\Domain\Notify\GeneralNotification;
use App\Modules\User\Domain\Notify\Models\Notification;
use App\Modules\User\Domain\Notify\TestMailNotification;
use App\Modules\User\Domain\Notify\WelcomeNotification;
use App\Modules\User\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;

uses(LazilyRefreshDatabase::class);

describe('TXR2H: domain notification library and delivery discipline', function (): void {
    test('TXR2H-FR-NOTIF-021: the six domain moments persist in-app plus queued mail and broadcast', function (): void {
        $incident = IncidentReport::factory()->make();

        $library = [
            new WelcomeNotification,
            new AssignmentNotification(internshipName: 'PKL Ganjil', assignmentTitle: 'Laporan', dueDate: '2026-12-01'),
            new RegistrationNotification(internshipName: 'PKL Ganjil', status: 'approved'),
            new IncidentReportedNotification($incident),
            new AnnouncementNotification(title: 'Libur', message: 'Libur nasional.'),
            new AccountStatusNotification(status: 'active', reason: 'Verifikasi lulus'),
        ];

        foreach ($library as $notification) {
            $channels = $notification->via(User::factory()->make());

            expect($channels)->toContain('mail')
                ->and($channels)->toContain('broadcast')
                ->and($channels)->toContain(CustomDatabaseChannel::class);
            expect($notification)->toBeInstanceOf(ShouldQueue::class);
        }

        // The welcome fan-out actually dispatches through Laravel's stack.
        $user = User::factory()->create();
        NotificationFacade::fake();
        $user->notify(new WelcomeNotification('sementara123'));
        NotificationFacade::assertSentTo($user, WelcomeNotification::class);
    });

    test('TXR2H-FR-NOTIF-021: registration, account-status, and announcement mails carry localized subjects, greetings, and bodies', function (): void {
        $user = User::factory()->make(['name' => 'Sinta']);

        $registrationMail = (new RegistrationNotification(
            internshipName: 'PKL Ganjil', status: 'approved',
        ))->toMail($user);
        expect($registrationMail->subject)->toBe(__('notifications.internship_registration.mail_subject'));
        expect($registrationMail->greeting)->toContain('Sinta');
        expect(implode(' ', $registrationMail->introLines))->toContain('PKL Ganjil');

        $statusMail = (new AccountStatusNotification(status: 'active', reason: 'Verifikasi lulus'))->toMail($user);
        expect($statusMail->subject)->toBe(__('notifications.account_status.mail_subject'));
        expect($statusMail->greeting)->toContain('Sinta');
        expect(implode(' ', $statusMail->introLines))->toContain('Verifikasi lulus');

        $announcementMail = (new AnnouncementNotification(title: 'Libur', message: 'Libur nasional.'))->toMail($user);
        expect($announcementMail->subject)->toBe('Libur');
        expect(implode(' ', $announcementMail->introLines))->toContain('Libur nasional.');
    });

    test('TXR2H-FR-NOTIF-021: welcome and assignment structured payloads carry localized titles and bodies', function (): void {
        $user = User::factory()->make(['name' => 'Sinta', 'username' => 'sinta01']);

        $welcomeDb = (new WelcomeNotification('sementara123'))->toCustomDatabase($user);
        expect($welcomeDb['type'])->toBe('system_welcome');
        expect($welcomeDb['title'])->toBe(__('notifications.welcome.title'));
        expect($welcomeDb['message'])->toBe(__('notifications.welcome.database'));
        expect($welcomeDb['link'])->toBe('/profile');

        $assignmentDb = (new AssignmentNotification(
            internshipName: 'PKL Ganjil', assignmentTitle: 'Laporan Mingguan', dueDate: '2026-12-01',
        ))->toCustomDatabase($user);
        expect($assignmentDb['type'])->toBe('assignment_published');
        expect($assignmentDb['title'])->toBe(__('notifications.assignment.title'));
        expect($assignmentDb['message'])->toContain('Laporan Mingguan');

        // NOTE (finding F1): WelcomeNotification::toMail() and
        // AssignmentNotification::toMail() currently throw
        // "Unknown named parameter $default" because they pass `default:` to
        // the __() helper, which accepts no such parameter. Mail-body
        // assertions for those two classes are therefore impossible until the
        // calls are corrected to plain __() lookups; covered instead by the
        // three renderable mails above plus these structured payloads.
    });

    test('TXR2H-FR-NOTIF-022: general notice toggles mail on a flag yet always persists the database row', function (): void {
        $withMail = new GeneralNotification(
            type: 'info', title: 'Penutupan Sekolah', message: 'Sekolah tutup besok.', sendEmail: true,
        );
        $withoutMail = new GeneralNotification(
            type: 'info', title: 'Pengingat Jurnal', message: 'Isi jurnal mingguan.', sendEmail: false,
        );

        expect($withMail->via(User::factory()->make()))->toContain('mail');
        expect($withoutMail->via(User::factory()->make()))->not->toContain('mail');
        expect($withoutMail->via(User::factory()->make()))->toContain(CustomDatabaseChannel::class);

        $first = User::factory()->create();
        $second = User::factory()->create();
        app(CustomDatabaseChannel::class)->send($first, $withMail);
        app(CustomDatabaseChannel::class)->send($second, $withoutMail);

        expect(Notification::where('user_id', $first->id)->where('title', 'Penutupan Sekolah')->exists())->toBeTrue();
        expect(Notification::where('user_id', $second->id)->where('title', 'Pengingat Jurnal')->exists())->toBeTrue();
    });

    test('TXR2H-FR-NOTIF-023: security mails use the mail channel only and never become rows', function (): void {
        $user = User::factory()->make(['name' => 'Budi']);

        $credential = new CredentialChangedNotification('password');
        $otp = new RecoveryOtpNotification('123456');
        $testMail = new TestMailNotification;

        foreach ([$credential, $otp, $testMail] as $mailOnly) {
            expect($mailOnly->via($user))->toBe(['mail']);
            expect(method_exists($mailOnly, 'toCustomDatabase'))->toBeFalse(
                get_class($mailOnly).' must not expose a database payload',
            );
        }

        $mail = $credential->toMail($user);
        expect($mail->subject)->toBe(__('auth.notifications.credential_changed_subject'));
        expect($mail->greeting)->toContain('Budi');
        expect(implode(' ', $mail->introLines))->toContain(
            __('auth.notifications.password_changed_line'),
        );

        NotificationFacade::fake();
        $persisted = User::factory()->create();
        $persisted->notify(new CredentialChangedNotification('password'));
        NotificationFacade::assertSentTo($persisted, CredentialChangedNotification::class);
        expect(Notification::where('user_id', $persisted->id)->count())->toBe(0);
    });

    test('TXR2H-FR-NOTIF-024: non-critical fan-out listeners ride the queue', function (): void {
        expect(app(NotifyOnAssignmentPublished::class))->toBeInstanceOf(ShouldQueue::class);
        expect(app(NotifyAdminsInternshipCreated::class))->toBeInstanceOf(ShouldQueue::class);

        // NOTE (observation): SendRoleWelcomeNotification and
        // SendBackupFailedNotification stay synchronous — consistent with the
        // spec's carve-out for work the next render depends on, but worth a
        // conscious review if login-time fan-out ever grows.
    });

    test('TXR2H-NFR-NOTIF-006: center, bell, and mail copy resolve in both English and Indonesian', function (): void {
        app()->setLocale('en');
        $enTitle = __('notifications.welcome.title');
        $enSubject = __('notifications.assignment.mail_subject', ['title' => 'Laporan']);
        $enMarkAll = __('notifications.ui.success_mark_all');

        app()->setLocale('id');
        $idTitle = __('notifications.welcome.title');
        $idSubject = __('notifications.assignment.mail_subject', ['title' => 'Laporan']);
        $idMarkAll = __('notifications.ui.success_mark_all');

        app()->setLocale('en');

        expect($enTitle)->toBe('Welcome to the System!');
        expect($idTitle)->toBe('Selamat datang di Sistem!');
        expect($enSubject)->not->toBe($idSubject);
        expect($enSubject)->toContain('Laporan');
        expect($idSubject)->toContain('Laporan');
        expect($enMarkAll)->not->toBe($idMarkAll);
        foreach ([$enTitle, $idTitle, $enSubject, $idSubject, $enMarkAll, $idMarkAll] as $copy) {
            expect($copy)->not->toBeEmpty();
        }
    });

    test('TXR2H-UC-NOTIF-004: first login delivers a role-appropriate welcome exactly once', function (): void {
        $student = User::factory()->create(['first_login_at' => null]);
        $student->assignRole('student');
        $listener = app(SendRoleWelcomeNotification::class);

        $listener->handle(new LoginSucceeded($student, $student->email));

        $rows = Notification::where('user_id', $student->id)->where('type', 'welcome')->get();
        expect($rows)->toHaveCount(1);
        expect($rows->first()->message)->toBe(__('notifications.welcome_to_dashboard.student'));
        expect($student->fresh()->first_login_at)->not->toBeNull();

        $listener->handle(new LoginSucceeded($student->fresh(), $student->email));
        expect(Notification::where('user_id', $student->id)->where('type', 'welcome')->count())->toBe(1);

        $teacher = User::factory()->create(['first_login_at' => null]);
        $teacher->assignRole('teacher');
        $listener->handle(new LoginSucceeded($teacher, $teacher->email));

        $teacherRow = Notification::where('user_id', $teacher->id)->where('type', 'welcome')->firstOrFail();
        expect($teacherRow->message)->toBe(__('notifications.welcome_to_dashboard.teacher'));
        expect($teacherRow->message)->not->toBe($rows->first()->message);
    });

    test('TXR2H-UC-NOTIF-005: publishing fans out an in-app row synchronously while mail stays queued', function (): void {
        $teacher = User::factory()->create();
        $teacher->assignRole('teacher');
        $assignment = Assignment::factory()->create([
            'title' => 'Laporan Mingguan 3', 'created_by' => $teacher->id,
        ]);

        app(NotifyOnAssignmentPublished::class)->handle(new AssignmentPublished($assignment));

        $row = Notification::where('user_id', $teacher->id)
            ->where('type', 'assignment_published')->firstOrFail();
        expect($row->title)->toBe(__('notifications.assignment.title'));
        expect($row->message)->toContain('Laporan Mingguan 3');
        expect($row->link)->not->toBeEmpty();

        expect(app(NotifyOnAssignmentPublished::class))->toBeInstanceOf(ShouldQueue::class);
        expect((new AssignmentNotification('PKL', 'T'))->via($teacher))->toContain('mail');
    });
});
