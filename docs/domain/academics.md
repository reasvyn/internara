# Academics Domain
> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Institutional aggregates reference

## Purpose

Academics manages institutional foundation — school profile, departments (program keahlian), and academic years.

---

## Design Principles

### 1. Single-Active Academic Year

Exactly one academic year is active at any time. Activating a new year automatically deactivates the previous one.

### 2. Department Lifecycle

Departments (program keahlian) are protected from deletion while they have active student profiles or programs.

---

## Domain Boundary

Academics owns the institution's structural data: school identity, department/program keahlian definitions, and academic year periods. It does not own runtime configuration (Settings), user accounts (User/Auth), or program definitions (Program).

---

## Key Features

- School profile editor: legal name, code, address, contact, logo
- Department manager: CRUD program keahlian with search, sort, paginate, bulk selection
- Academic year manager: CRUD with single-active constraint and bulk delete
