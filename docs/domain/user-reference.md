# User — API Reference

Total: 16 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Actions/GetStudentDashboardDataAction.php` | `GetStudentDashboardDataAction` | `BaseAction` | Gathers all data needed for student dashboard |
| `User/Actions/UpdateProfileAction.php` | `UpdateProfileAction` | `BaseAction` | Updates user profile with avatar upload |

## Controllers

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Http/Controllers/DashboardController.php` | `DashboardController` | `BaseController` | Redirects users to role-appropriate dashboard |
| `User/Http/Controllers/HomeController.php` | `HomeController` | `BaseController` | Handles root URL routing (setup vs login) |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `User/Enums/BloodType.php` | `BloodType` | `LabelEnum` | Blood type enum |
| `User/Enums/Gender.php` | `Gender` | `LabelEnum` | Gender enum |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Livewire/ProfileEditor.php` | `ProfileEditor` | `Component` | Profile editing form (with avatar upload and password change) |
| `User/Livewire/RecentActivityList.php` | `RecentActivityList` | `Component` | Recent activity log listing |
| `User/Livewire/UserDashboard.php` | `UserDashboard` | `Component` | Generic user dashboard with recent activity |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `User/Livewire/Forms/ProfileForm.php` | `ProfileForm` | `Form` | name, email, phone, address, bio | `ProfileEditor` |
| `User/Livewire/Forms/PasswordForm.php` | `PasswordForm` | `Form` | current_password, password, password_confirmation | `ProfileEditor` |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Models/Profile.php` | `Profile` | `BaseModel` | Eloquent model for user profiles (department, school, bio) |
| `User/Models/User.php` | `User` | `Model` (Authenticatable) | Eloquent model for users (with relations to mentor, mentee, roles) |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `User/Notifications/TestMailNotification.php` | `TestMailNotification` | `Notification` | Simple test mail notification |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `User/Policies/ProfilePolicy.php` | `ProfilePolicy` | `BasePolicy` | Authorization for profile operations |

## Rules

| File | Class | Implements | Description |
|---|---|---|---|
| `User/Rules/SystemUsername.php` | `SystemUsername` | `ValidationRule` | Validates system-generated usernames |

## Services

| File | Class | Description |
|---|---|---|
| `User/Services/DashboardService.php` | `DashboardService` | Resolves role-appropriate dashboard for a user |

## Support

| File | Class | Description |
|---|---|---|
| `User/Support/UserIdentifierGenerator.php` | `UserIdentifierGenerator` | Generates unique system usernames |
