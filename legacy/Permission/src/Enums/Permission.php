<?php

declare(strict_types=1);

namespace Modules\Permission\Enums;

enum Permission: string
{
    // ==================== CORE ====================
    case CORE_VIEW_DASHBOARD = 'core.view-dashboard';
    case CORE_VIEW_AUDIT = 'core.view-audit';
    case CORE_EXPORT_DATA = 'core.export-data';

    // ==================== USER ====================
    case USER_VIEW = 'user.view';
    case USER_CREATE = 'user.create';
    case USER_UPDATE = 'user.update';
    case USER_DELETE = 'user.delete';
    case USER_MANAGE = 'user.manage';

    // ==================== PROFILE ====================
    case PROFILE_VIEW = 'profile.view';
    case PROFILE_UPDATE = 'profile.update';

    // ==================== SCHOOL ====================
    case SCHOOL_VIEW = 'school.view';
    case SCHOOL_CREATE = 'school.create';
    case SCHOOL_UPDATE = 'school.update';
    case SCHOOL_DELETE = 'school.delete';
    case SCHOOL_MANAGE = 'school.manage';

    // ==================== DEPARTMENT ====================
    case DEPARTMENT_VIEW = 'department.view';
    case DEPARTMENT_CREATE = 'department.create';
    case DEPARTMENT_UPDATE = 'department.update';
    case DEPARTMENT_DELETE = 'department.delete';
    case DEPARTMENT_MANAGE = 'department.manage';

    // ==================== INTERNSHIP ====================
    case INTERNSHIP_VIEW = 'internship.view';
    case INTERNSHIP_CREATE = 'internship.create';
    case INTERNSHIP_UPDATE = 'internship.update';
    case INTERNSHIP_DELETE = 'internship.delete';
    case INTERNSHIP_MANAGE = 'internship.manage';
    case INTERNSHIP_APPROVE = 'internship.approve';

    // ==================== REGISTRATION ====================
    case REGISTRATION_VIEW = 'registration.view';
    case REGISTRATION_CREATE = 'registration.create';
    case REGISTRATION_UPDATE = 'registration.update';
    case REGISTRATION_CANCEL = 'registration.cancel';
    case REGISTRATION_APPROVE = 'registration.approve';

    // ==================== PLACEMENT ====================
    case PLACEMENT_VIEW = 'placement.view';
    case PLACEMENT_CREATE = 'placement.create';
    case PLACEMENT_UPDATE = 'placement.update';
    case PLACEMENT_DELETE = 'placement.delete';
    case PLACEMENT_MANAGE = 'placement.manage';

    // ==================== COMPANY ====================
    case COMPANY_VIEW = 'company.view';
    case COMPANY_CREATE = 'company.create';
    case COMPANY_UPDATE = 'company.update';
    case COMPANY_DELETE = 'company.delete';
    case COMPANY_MANAGE = 'company.manage';

    // ==================== ATTENDANCE ====================
    case ATTENDANCE_VIEW = 'attendance.view';
    case ATTENDANCE_CREATE = 'attendance.create';
    case ATTENDANCE_UPDATE = 'attendance.update';
    case ATTENDANCE_MANAGE = 'attendance.manage';
    case ATTENDANCE_APPROVE = 'attendance.approve';

    // ==================== JOURNAL ====================
    case JOURNAL_VIEW = 'journal.view';
    case JOURNAL_CREATE = 'journal.create';
    case JOURNAL_UPDATE = 'journal.update';
    case JOURNAL_MANAGE = 'journal.manage';
    case JOURNAL_APPROVE = 'journal.approve';

    // ==================== ASSESSMENT ====================
    case ASSESSMENT_VIEW = 'assessment.view';
    case ASSESSMENT_CREATE = 'assessment.create';
    case ASSESSMENT_UPDATE = 'assessment.update';
    case ASSESSMENT_MANAGE = 'assessment.manage';
    case ASSESSMENT_GRADE = 'assessment.grade';

    // ==================== ASSIGNMENT ====================
    case ASSIGNMENT_VIEW = 'assignment.view';
    case ASSIGNMENT_CREATE = 'assignment.create';
    case ASSIGNMENT_UPDATE = 'assignment.update';
    case ASSIGNMENT_MANAGE = 'assignment.manage';
    case ASSIGNMENT_GRADE = 'assignment.grade';

    // ==================== SCHEDULE ====================
    case SCHEDULE_VIEW = 'schedule.view';
    case SCHEDULE_CREATE = 'schedule.create';
    case SCHEDULE_UPDATE = 'schedule.update';
    case SCHEDULE_MANAGE = 'schedule.manage';

    // ==================== GUIDANCE ====================
    case GUIDANCE_VIEW = 'guidance.view';
    case GUIDANCE_MANAGE = 'guidance.manage';

    // ==================== REPORT ====================
    case REPORT_VIEW = 'report.view';
    case REPORT_GENERATE = 'report.generate';
    case REPORT_EXPORT = 'report.export';

    // ==================== SETTING ====================
    case SETTING_VIEW = 'setting.view';
    case SETTING_MANAGE = 'setting.manage';

    // ==================== MEDIA ====================
    case MEDIA_VIEW = 'media.view';
    case MEDIA_UPLOAD = 'media.upload';
    case MEDIA_DELETE = 'media.delete';

    // ==================== NOTIFICATION ====================
    case NOTIFICATION_VIEW = 'notification.view';
    case NOTIFICATION_SEND = 'notification.send';
    case NOTIFICATION_MANAGE = 'notification.manage';

    // ==================== ADMIN (super-admin only) ====================
    case ADMIN_VIEW = 'admin.view';
    case ADMIN_MANAGE = 'admin.manage';

    // ==================== STUDENT ====================
    case STUDENT_VIEW = 'student.view';
    case STUDENT_MANAGE = 'student.manage';

    // ==================== TEACHER ====================
    case TEACHER_VIEW = 'teacher.view';
    case TEACHER_MANAGE = 'teacher.manage';

    // ==================== MENTOR ====================
    case MENTOR_VIEW = 'mentor.view';
    case MENTOR_MANAGE = 'mentor.manage';

    /**
     * Get all permissions grouped by module.
     *
     * @return array<string, list<self>>
     */
    public static function grouped(): array
    {
        return [
            'core' => [self::CORE_VIEW_DASHBOARD, self::CORE_VIEW_AUDIT, self::CORE_EXPORT_DATA],
            'user' => [
                self::USER_VIEW,
                self::USER_CREATE,
                self::USER_UPDATE,
                self::USER_DELETE,
                self::USER_MANAGE,
            ],
            'profile' => [self::PROFILE_VIEW, self::PROFILE_UPDATE],
            'school' => [
                self::SCHOOL_VIEW,
                self::SCHOOL_CREATE,
                self::SCHOOL_UPDATE,
                self::SCHOOL_DELETE,
                self::SCHOOL_MANAGE,
            ],
            'department' => [
                self::DEPARTMENT_VIEW,
                self::DEPARTMENT_CREATE,
                self::DEPARTMENT_UPDATE,
                self::DEPARTMENT_DELETE,
                self::DEPARTMENT_MANAGE,
            ],
            'internship' => [
                self::INTERNSHIP_VIEW,
                self::INTERNSHIP_CREATE,
                self::INTERNSHIP_UPDATE,
                self::INTERNSHIP_DELETE,
                self::INTERNSHIP_MANAGE,
                self::INTERNSHIP_APPROVE,
            ],
            'registration' => [
                self::REGISTRATION_VIEW,
                self::REGISTRATION_CREATE,
                self::REGISTRATION_UPDATE,
                self::REGISTRATION_CANCEL,
                self::REGISTRATION_APPROVE,
            ],
            'placement' => [
                self::PLACEMENT_VIEW,
                self::PLACEMENT_CREATE,
                self::PLACEMENT_UPDATE,
                self::PLACEMENT_DELETE,
                self::PLACEMENT_MANAGE,
            ],
            'company' => [
                self::COMPANY_VIEW,
                self::COMPANY_CREATE,
                self::COMPANY_UPDATE,
                self::COMPANY_DELETE,
                self::COMPANY_MANAGE,
            ],
            'attendance' => [
                self::ATTENDANCE_VIEW,
                self::ATTENDANCE_CREATE,
                self::ATTENDANCE_UPDATE,
                self::ATTENDANCE_MANAGE,
                self::ATTENDANCE_APPROVE,
            ],
            'journal' => [
                self::JOURNAL_VIEW,
                self::JOURNAL_CREATE,
                self::JOURNAL_UPDATE,
                self::JOURNAL_MANAGE,
                self::JOURNAL_APPROVE,
            ],
            'assessment' => [
                self::ASSESSMENT_VIEW,
                self::ASSESSMENT_CREATE,
                self::ASSESSMENT_UPDATE,
                self::ASSESSMENT_MANAGE,
                self::ASSESSMENT_GRADE,
            ],
            'assignment' => [
                self::ASSIGNMENT_VIEW,
                self::ASSIGNMENT_CREATE,
                self::ASSIGNMENT_UPDATE,
                self::ASSIGNMENT_MANAGE,
                self::ASSIGNMENT_GRADE,
            ],
            'schedule' => [
                self::SCHEDULE_VIEW,
                self::SCHEDULE_CREATE,
                self::SCHEDULE_UPDATE,
                self::SCHEDULE_MANAGE,
            ],
            'guidance' => [self::GUIDANCE_VIEW, self::GUIDANCE_MANAGE],
            'report' => [self::REPORT_VIEW, self::REPORT_GENERATE, self::REPORT_EXPORT],
            'setting' => [self::SETTING_VIEW, self::SETTING_MANAGE],
            'media' => [self::MEDIA_VIEW, self::MEDIA_UPLOAD, self::MEDIA_DELETE],
            'notification' => [
                self::NOTIFICATION_VIEW,
                self::NOTIFICATION_SEND,
                self::NOTIFICATION_MANAGE,
            ],
            'admin' => [self::ADMIN_VIEW, self::ADMIN_MANAGE],
            'student' => [self::STUDENT_VIEW, self::STUDENT_MANAGE],
            'teacher' => [self::TEACHER_VIEW, self::TEACHER_MANAGE],
            'mentor' => [self::MENTOR_VIEW, self::MENTOR_MANAGE],
        ];
    }

    /**
     * Get all unique permission values.
     *
     * @return list<string>
     */
    public static function allValues(): array
    {
        return array_values(array_map(fn (self $case) => $case->value, self::cases()));
    }
}
