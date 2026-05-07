# Architecture Overview: Action-Oriented MVC

Internara uses an **Action-Oriented MVC** architecture designed for rapid prototyping and clean organization. Files are grouped primarily by their **Functional Layer**, and secondarily by their **Business Context (Domain)**.

## Directory Structure

The system is organized under the `app/` directory. Each layer contains sub-directories for specific business contexts.

```text
app/
├── Actions/                    # BUSINESS LOGIC (Entry points)
│   └── {Context}/              # e.g., Internship, User, Auth
│       └── *Action.php         # Single-purpose classes with execute()
│
├── Models/                     # PERSISTENCE (Eloquent)
│   └── {Context}/              # e.g., Internship, User, School
│
├── Livewire/                   # PRESENTATION (Reactive UI)
│   └── {Context}/
│
├── Enums/                      # CONSTANTS & TYPES
│   └── {Context}/
│
├── Data/                       # DATA OBJECTS (DTOs)
│   └── {Context}/
│
├── Exceptions/                 # ERROR HANDLING
│   └── {Context}/
│
├── Notifications/              # COMMUNICATION
│   └── {Context}/
│
├── Policies/                   # AUTHORIZATION
│   └── {Context}/
│
└── Services/                   # INFRASTRUCTURE / UTILITIES
    └── {Context}/
```

## Key Principles

| Principle | Rule |
|---|---|
| **Layer-First** | Top-level folders represent the type of object (Action, Model, Livewire). |
| **Context-Grouped** | Inside each layer, files are grouped by business domain (User, Internship). |
| **Action Pattern** | Logic resides in classes named `*Action` with a single public `execute()` method. |
| **Thin Controllers** | Controllers and Livewire components delegate all business logic to Actions. |
| **Direct Models** | Eloquent Models are used directly for database interactions. |

## Role Mapping (Context: High School)

- **Mentee** Context: Refers to **Students** participating in the program.
- **Mentor** Context: Refers to **Teachers** and **Industry Supervisors**.
- **System**: strictly Senior/Vocational High School level.

## Data Flow

```
User Input → Livewire/Controller → Action → Eloquent Model → Database
                                    ↓
                              Flash/Notification
```

## Naming Convention Examples

- Action: `app/Actions/Internship/ApproveRegistrationAction.php`
- Model: `app/Models/User/Profile.php`
- Livewire: `app/Livewire/Internship/RegistrationList.php`
- Enum: `app/Enums/Internship/InternshipStatus.php`
- DTO: `app/Data/Internship/RegistrationData.php`

## Strategic Advantage

This architecture provides the speed of standard Laravel development while maintaining a clear separation of concerns. By isolating logic into Actions, the application remains easy to test and provides a clear roadmap if a future transition to a different technology stack (e.g., Next.js/TypeScript) is desired.
