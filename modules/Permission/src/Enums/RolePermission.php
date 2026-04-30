<?php

declare(strict_types=1);

namespace Modules\Permission\Enums;

enum RolePermission: string
{
    case SUPER_ADMIN = 'super-admin';
    case ADMIN = 'admin';
    case TEACHER = 'teacher';
    case MENTOR = 'mentor';
    case STUDENT = 'student';

    /**
     * Get permissions for this role.
     *
     * @return list<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::SUPER_ADMIN => $this->allPermissions(),
            self::ADMIN => $this->adminPermissions(),
            self::TEACHER => $this->teacherPermissions(),
            self::MENTOR => $this->mentorPermissions(),
            self::STUDENT => $this->studentPermissions(),
        };
    }

    /**
     * All permissions for Super Admin.
     *
     * @return list<string>
     */
    protected function allPermissions(): array
    {
        return array_merge(
            Permission::allValues(),
        );
    }

    /**
     * Permissions for Admin.
     *
     * @return list<string>
     */
    protected function adminPermissions(): array
    {
        return [
            // Core
            Permission::CORE_VIEW_DASHBOARD->value,
            Permission::CORE_VIEW_AUDIT->value,
            Permission::CORE_EXPORT_DATA->value,

            // User
            Permission::USER_VIEW->value,
            Permission::USER_CREATE->value,
            Permission::USER_UPDATE->value,
            Permission::USER_MANAGE->value,

            // Profile
            Permission::PROFILE_VIEW->value,
            Permission::PROFILE_UPDATE->value,

            // School
            Permission::SCHOOL_VIEW->value,
            Permission::SCHOOL_UPDATE->value,
            Permission::SCHOOL_MANAGE->value,

            // Department
            Permission::DEPARTMENT_VIEW->value,
            Permission::DEPARTMENT_CREATE->value,
            Permission::DEPARTMENT_UPDATE->value,
            Permission::DEPARTMENT_MANAGE->value,

            // Internship
            Permission::INTERNSHIP_VIEW->value,
            Permission::INTERNSHIP_UPDATE->value,
            Permission::INTERNSHIP_MANAGE->value,
            Permission::INTERNSHIP_APPROVE->value,

            // Registration
            Permission::REGISTRATION_VIEW->value,
            Permission::REGISTRATION_CREATE->value,
            Permission::REGISTRATION_UPDATE->value,
            Permission::REGISTRATION_CANCEL->value,
            Permission::REGISTRATION_APPROVE->value,

            // Placement
            Permission::PLACEMENT_VIEW->value,
            Permission::PLACEMENT_CREATE->value,
            Permission::PLACEMENT_UPDATE->value,
            Permission::PLACEMENT_MANAGE->value,

            // Company
            Permission::COMPANY_VIEW->value,
            Permission::COMPANY_CREATE->value,
            Permission::COMPANY_UPDATE->value,
            Permission::COMPANY_MANAGE->value,

            // Attendance
            Permission::ATTENDANCE_VIEW->value,
            Permission::ATTENDANCE_CREATE->value,
            Permission::ATTENDANCE_UPDATE->value,
            Permission::ATTENDANCE_MANAGE->value,
            Permission::ATTENDANCE_APPROVE->value,

            // Journal
            Permission::JOURNAL_VIEW->value,
            Permission::JOURNAL_CREATE->value,
            Permission::JOURNAL_UPDATE->value,
            Permission::JOURNAL_MANAGE->value,
            Permission::JOURNAL_APPROVE->value,

            // Assessment
            Permission::ASSESSMENT_VIEW->value,
            Permission::ASSESSMENT_CREATE->value,
            Permission::ASSESSMENT_UPDATE->value,
            Permission::ASSESSMENT_MANAGE->value,
            Permission::ASSESSMENT_GRADE->value,

            // Assignment
            Permission::ASSIGNMENT_VIEW->value,
            Permission::ASSIGNMENT_CREATE->value,
            Permission::ASSIGNMENT_UPDATE->value,
            Permission::ASSIGNMENT_MANAGE->value,
            Permission::ASSIGNMENT_GRADE->value,

            // Schedule
            Permission::SCHEDULE_VIEW->value,
            Permission::SCHEDULE_CREATE->value,
            Permission::SCHEDULE_UPDATE->value,
            Permission::SCHEDULE_MANAGE->value,

            // Guidance
            Permission::GUIDANCE_VIEW->value,
            Permission::GUIDANCE_MANAGE->value,

            // Report
            Permission::REPORT_VIEW->value,
            Permission::REPORT_GENERATE->value,
            Permission::REPORT_EXPORT->value,

            // Setting
            Permission::SETTING_VIEW->value,
            Permission::SETTING_MANAGE->value,

            // Media
            Permission::MEDIA_VIEW->value,
            Permission::MEDIA_UPLOAD->value,
            Permission::MEDIA_DELETE->value,

            // Notification
            Permission::NOTIFICATION_VIEW->value,
            Permission::NOTIFICATION_SEND->value,
            Permission::NOTIFICATION_MANAGE->value,

            // Admin
            Permission::ADMIN_VIEW->value,
            Permission::ADMIN_MANAGE->value,

            // Student
            Permission::STUDENT_VIEW->value,
            Permission::STUDENT_MANAGE->value,

            // Teacher
            Permission::TEACHER_VIEW->value,
            Permission::TEACHER_MANAGE->value,

            // Mentor
            Permission::MENTOR_VIEW->value,
            Permission::MENTOR_MANAGE->value,
        ];
    }

    /**
     * Permissions for Teacher.
     *
     * @return list<string>
     */
    protected function teacherPermissions(): array
    {
        return [
            // Core
            Permission::CORE_VIEW_DASHBOARD->value,

            // Internship
            Permission::INTERNSHIP_VIEW->value,
            Permission::INTERNSHIP_UPDATE->value,
            Permission::INTERNSHIP_APPROVE->value,

            // Registration
            Permission::REGISTRATION_VIEW->value,
            Permission::REGISTRATION_CREATE->value,
            Permission::REGISTRATION_APPROVE->value,

            // Placement
            Permission::PLACEMENT_VIEW->value,
            Permission::PLACEMENT_UPDATE->value,

            // Attendance
            Permission::ATTENDANCE_VIEW->value,
            Permission::ATTENDANCE_CREATE->value,
            Permission::ATTENDANCE_MANAGE->value,
            Permission::ATTENDANCE_APPROVE->value,

            // Journal
            Permission::JOURNAL_VIEW->value,
            Permission::JOURNAL_CREATE->value,
            Permission::JOURNAL_UPDATE->value,
            Permission::JOURNAL_MANAGE->value,
            Permission::JOURNAL_APPROVE->value,

            // Assessment
            Permission::ASSESSMENT_VIEW->value,
            Permission::ASSESSMENT_CREATE->value,
            Permission::ASSESSMENT_UPDATE->value,
            Permission::ASSESSMENT_MANAGE->value,
            Permission::ASSESSMENT_GRADE->value,

            // Assignment
            Permission::ASSIGNMENT_VIEW->value,
            Permission::ASSIGNMENT_CREATE->value,
            Permission::ASSIGNMENT_UPDATE->value,
            Permission::ASSIGNMENT_MANAGE->value,
            Permission::ASSIGNMENT_GRADE->value,

            // Schedule
            Permission::SCHEDULE_VIEW->value,

            // Report
            Permission::REPORT_VIEW->value,
            Permission::REPORT_GENERATE->value,
        ];
    }

    /**
     * Permissions for Mentor.
     *
     * @return list<string>
     */
    protected function mentorPermissions(): array
    {
        return [
            // Core
            Permission::CORE_VIEW_DASHBOARD->value,

            // Internship
            Permission::INTERNSHIP_VIEW->value,
            Permission::INTERNSHIP_UPDATE->value,

            // Registration
            Permission::REGISTRATION_VIEW->value,

            // Placement
            Permission::PLACEMENT_VIEW->value,
            Permission::PLACEMENT_UPDATE->value,

            // Company
            Permission::COMPANY_VIEW->value,

            // Attendance
            Permission::ATTENDANCE_VIEW->value,
            Permission::ATTENDANCE_CREATE->value,
            Permission::ATTENDANCE_UPDATE->value,

            // Journal
            Permission::JOURNAL_VIEW->value,
            Permission::JOURNAL_CREATE->value,
            Permission::JOURNAL_UPDATE->value,
            Permission::JOURNAL_MANAGE->value,
            Permission::JOURNAL_APPROVE->value,

            // Assessment
            Permission::ASSESSMENT_VIEW->value,
            Permission::ASSESSMENT_CREATE->value,
            Permission::ASSESSMENT_UPDATE->value,
            Permission::ASSESSMENT_MANAGE->value,
            Permission::ASSESSMENT_GRADE->value,

            // Assignment
            Permission::ASSIGNMENT_VIEW->value,
            Permission::ASSIGNMENT_CREATE->value,
            Permission::ASSIGNMENT_UPDATE->value,
            Permission::ASSIGNMENT_MANAGE->value,
            Permission::ASSIGNMENT_GRADE->value,

            // Schedule
            Permission::SCHEDULE_VIEW->value,

            // Report
            Permission::REPORT_VIEW->value,
        ];
    }

    /**
     * Permissions for Student.
     *
     * @return list<string>
     */
    protected function studentPermissions(): array
    {
        return [
            // Core
            Permission::CORE_VIEW_DASHBOARD->value,

            // Profile
            Permission::PROFILE_VIEW->value,
            Permission::PROFILE_UPDATE->value,

            // Internship
            Permission::INTERNSHIP_VIEW->value,

            // Registration
            Permission::REGISTRATION_VIEW->value,
            Permission::REGISTRATION_CREATE->value,

            // Placement
            Permission::PLACEMENT_VIEW->value,

            // Company
            Permission::COMPANY_VIEW->value,

            // Attendance
            Permission::ATTENDANCE_VIEW->value,
            Permission::ATTENDANCE_CREATE->value,
            Permission::ATTENDANCE_UPDATE->value,

            // Journal
            Permission::JOURNAL_VIEW->value,
            Permission::JOURNAL_CREATE->value,
            Permission::JOURNAL_UPDATE->value,

            // Assignment
            Permission::ASSIGNMENT_VIEW->value,
            Permission::ASSIGNMENT_CREATE->value,
            Permission::ASSIGNMENT_UPDATE->value,

            // Schedule
            Permission::SCHEDULE_VIEW->value,

            // Report
            Permission::REPORT_VIEW->value,

            // Guidance
            Permission::GUIDANCE_VIEW->value,
        ];
    }
}
