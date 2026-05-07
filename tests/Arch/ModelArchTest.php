<?php

declare(strict_types=1);

use App\Models\AbsenceRequest;
use App\Models\AcademicYear;
use App\Models\Assessment;
use App\Models\Assignment;
use App\Models\AssignmentType;
use App\Models\AttendanceLog;
use App\Models\BaseModel;
use App\Models\Company;
use App\Models\Competency;
use App\Models\Department;
use App\Models\DepartmentCompetency;
use App\Models\DocumentTemplate;
use App\Models\GeneratedReport;
use App\Models\Handbook;
use App\Models\HandbookAcknowledgement;
use App\Models\Internship;
use App\Models\LogbookEntry;
use App\Models\MentorEvaluation;
use App\Models\MonitoringVisit;
use App\Models\Notification;
use App\Models\OfficialDocument;
use App\Models\Placement;
use App\Models\Profile;
use App\Models\Registration;
use App\Models\Requirement;
use App\Models\RequirementSubmission;
use App\Models\Schedule;
use App\Models\School;
use App\Models\StudentCompetencyLog;
use App\Models\Submission;
use App\Models\SupervisionLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

arch('all domain models must extend BaseModel')
    ->expect([
        Assessment::class,
        Competency::class,
        DepartmentCompetency::class,
        Assignment::class,
        AssignmentType::class,
        Submission::class,
        AttendanceLog::class,
        AbsenceRequest::class,
        DocumentTemplate::class,
        OfficialDocument::class,
        GeneratedReport::class,
        Handbook::class,
        HandbookAcknowledgement::class,
        Company::class,
        Internship::class,
        Placement::class,
        Registration::class,
        Requirement::class,
        RequirementSubmission::class,
        LogbookEntry::class,
        StudentCompetencyLog::class,
        MentorEvaluation::class,
        MonitoringVisit::class,
        SupervisionLog::class,
        Notification::class,
        AcademicYear::class,
        Department::class,
        School::class,
        Profile::class,
        Schedule::class,
    ])
    ->toExtend(BaseModel::class);

arch('all models must use HasUuids trait')
    ->expect([
        Assessment::class,
        Competency::class,
        DepartmentCompetency::class,
        Assignment::class,
        AssignmentType::class,
        Submission::class,
        AttendanceLog::class,
        AbsenceRequest::class,
        DocumentTemplate::class,
        OfficialDocument::class,
        GeneratedReport::class,
        Handbook::class,
        HandbookAcknowledgement::class,
        Company::class,
        Internship::class,
        Placement::class,
        Registration::class,
        Requirement::class,
        RequirementSubmission::class,
        LogbookEntry::class,
        StudentCompetencyLog::class,
        MentorEvaluation::class,
        MonitoringVisit::class,
        SupervisionLog::class,
        Notification::class,
        AcademicYear::class,
        Department::class,
        School::class,
        Profile::class,
        Schedule::class,
        User::class,
    ])
    ->toUseTraits([
        HasUuids::class,
    ]);
