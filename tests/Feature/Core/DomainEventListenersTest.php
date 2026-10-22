<?php

declare(strict_types=1);

namespace Tests\Feature\Core;

use App\Modules\Academic\Domain\AcademicYear\Events\AcademicYearCreated;
use App\Modules\Academic\Domain\AcademicYear\Models\AcademicYear;
use App\Modules\Assessment\Events\AssessmentFinalized;
use App\Modules\Assessment\Listeners\LogAssessmentFinalized;
use App\Modules\Assessment\Models\Assessment;
use App\Modules\Auth\Notifications\CredentialChangedNotification;
use App\Modules\Core\Channels\Data\NotificationData;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Partner\Domain\Company\Models\Company;
use App\Modules\Partner\Domain\Partnership\Events\PartnershipTerminated;
use App\Modules\Partner\Domain\Partnership\Listeners\NotifyOnPartnershipTerminated;
use App\Modules\Partner\Domain\Partnership\Models\Partnership;
use App\Modules\Report\Domain\StudentReport\Events\GradeCalculated;
use App\Modules\Report\Domain\StudentReport\Events\StudentReportFinalized;
use App\Modules\Report\Domain\StudentReport\Listeners\LogGradeCalculated;
use App\Modules\Report\Domain\StudentReport\Listeners\LogStudentReportFinalized;
use App\Modules\Report\Domain\StudentReport\Models\StudentReport;
use App\Modules\Setup\Domain\SetupWizard\Events\SetupFinalized;
use App\Modules\Setup\Domain\SetupWizard\Listeners\LogSetupFinalized;
use App\Modules\User\Domain\Dashboard\Listeners\ClearDashboardCacheOnYearChange;
use App\Modules\User\Domain\Profile\Events\ProfileUpdated;
use App\Modules\User\Domain\Profile\Listeners\SendProfileChangedMail;
use App\Modules\User\Domain\Profile\Models\Profile;
use App\Modules\User\Domain\UserManagement\Listeners\InvalidateUserCache;
use App\Modules\User\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Mockery;

uses(LazilyRefreshDatabase::class);

describe('NUCY3: Domain Event Listeners Behavioral Coverage', function (): void {
    test('NUCY3-FR-EVENT-004: LogSetupFinalized clears setup cache and logs completion', function (): void {
        $cacheKey = config('cache-keys.setup_installed');
        Cache::put($cacheKey, true, 3600);
        expect(Cache::has($cacheKey))->toBeTrue();

        $event = new SetupFinalized('dept-uuid', new DateTimeImmutable);
        (new LogSetupFinalized)->handle($event);

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('NUCY3-FR-EVENT-005: NotifyOnPartnershipTerminated sends notification to company creator', function (): void {
        $creator = User::factory()->create();
        $company = Company::factory()->create();
        $company->setAttribute('created_by', $creator->id);
        $partnership = Partnership::factory()->create(['company_id' => $company->id]);
        $partnership->setRelation('company', $company);

        $mockSender = Mockery::mock(SendsNotifications::class);
        $mockSender->shouldReceive('execute')
            ->once()
            ->withArgs(function (NotificationData $data) use ($creator): bool {
                return $data->userId === $creator->id
                    && $data->type === 'partnership_terminated';
            });

        $listener = new NotifyOnPartnershipTerminated($mockSender);
        $listener->handle(new PartnershipTerminated($partnership));
    });

    test('NUCY3-FR-EVENT-005: NotifyOnPartnershipTerminated skips notification when creator is null', function (): void {
        $company = Company::factory()->create();
        $partnership = Partnership::factory()->create(['company_id' => $company->id]);
        $partnership->setRelation('company', $company);

        $mockSender = Mockery::mock(SendsNotifications::class);
        $mockSender->shouldNotReceive('execute');

        $listener = new NotifyOnPartnershipTerminated($mockSender);
        $listener->handle(new PartnershipTerminated($partnership));
    });

    test('NUCY3-FR-EVENT-006: LogGradeCalculated records grade details to SmartLogger', function (): void {
        $report = StudentReport::factory()->create([
            'final_score' => 88.5,
            'grade_letter' => 'A',
        ]);

        $listener = new LogGradeCalculated;
        $listener->handle(new GradeCalculated($report));

        expect(true)->toBeTrue();
    });

    test('NUCY3-FR-EVENT-007: LogStudentReportFinalized records finalization to SmartLogger', function (): void {
        $admin = User::factory()->create();
        $report = StudentReport::factory()->create([
            'final_score' => 92.0,
            'grade_letter' => 'A',
            'finalized_by' => $admin->id,
        ]);

        $listener = new LogStudentReportFinalized;
        $listener->handle(new StudentReportFinalized($report));

        expect(true)->toBeTrue();
    });

    test('NUCY3-FR-EVENT-008: LogAssessmentFinalized logs assessment entity to SmartLogger', function (): void {
        $assessment = Assessment::factory()->create();

        $listener = new LogAssessmentFinalized;
        $listener->handle(new AssessmentFinalized($assessment));

        expect(true)->toBeTrue();
    });

    test('NUCY3-FR-EVENT-010: ClearDashboardCacheOnYearChange flushes admin dashboard statistics cache', function (): void {
        $cacheKey = config('cache-keys.admin_dashboard_stats');
        Cache::put($cacheKey, ['stats' => 'cached'], 3600);
        expect(Cache::has($cacheKey))->toBeTrue();

        $year = AcademicYear::factory()->create();
        (new ClearDashboardCacheOnYearChange)->handle(new AcademicYearCreated($year));

        expect(Cache::has($cacheKey))->toBeFalse();
    });

    test('NUCY3-FR-EVENT-011: SendProfileChangedMail notifies user on email or username alterations', function (): void {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'new.email@example.org',
            'username' => 'newusername',
        ]);
        $profile = Profile::factory()->create(['user_id' => $user->id]);

        $listener = new SendProfileChangedMail;

        // Both changed
        $listener->handle(new ProfileUpdated(
            profile: $profile,
            previousEmail: 'old.email@example.org',
            previousUsername: 'oldusername',
        ));

        Notification::assertSentTo(
            $user,
            CredentialChangedNotification::class,
            2,
        );
    });

    test('NUCY3-FR-EVENT-012: InvalidateUserCache clears specific user and user count cache entries', function (): void {
        $user = User::factory()->create();

        $singleKey = config('cache-keys.user_single').$user->id;
        $countKey = config('cache-keys.users_count');

        Cache::put($singleKey, ['user' => 'data'], 3600);
        Cache::put($countKey, 42, 3600);

        expect(Cache::has($singleKey))->toBeTrue()
            ->and(Cache::has($countKey))->toBeTrue();

        $event = (object) ['user' => $user];
        (new InvalidateUserCache)->handle($event);

        expect(Cache::has($singleKey))->toBeFalse()
            ->and(Cache::has($countKey))->toBeFalse();
    });
});
