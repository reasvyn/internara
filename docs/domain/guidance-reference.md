# Guidance — API Reference
> Last updated: 2026-05-23
> Changes: fix: complete system initialization overhaul — security, middleware, recovery, form objects, docs


Total: 8 files

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Actions/AcknowledgeHandbookAction.php` | `AcknowledgeHandbookAction` | `BaseAction` | Records student acknowledgment of a handbook |
| `Guidance/Actions/CreateHandbookAction.php` | `CreateHandbookAction` | `BaseAction` | Creates a new handbook with slug |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Entities/HandbookPublishState.php` | `HandbookPublishState` | `BaseEntity` | Read-only DTO for handbook publish state |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Livewire/HandbookIndex.php` | `HandbookIndex` | `Component` | Admin handbook management with pagination |
| `Guidance/Livewire/StudentHandbookIndex.php` | `StudentHandbookIndex` | `Component` | Student handbook view with acknowledgment |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Models/Handbook.php` | `Handbook` | `BaseModel` | Eloquent model for handbooks |
| `Guidance/Models/HandbookAcknowledgement.php` | `HandbookAcknowledgement` | `BaseModel` | Eloquent model for handbook acknowledgment records |

## Policies

| File | Class | Extends | Description |
|---|---|---|---|
| `Guidance/Policies/HandbookPolicy.php` | `HandbookPolicy` | `BasePolicy` | Authorization for handbook operations |
