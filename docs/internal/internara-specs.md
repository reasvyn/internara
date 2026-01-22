# Internara Specs

## Product Overview

**Product Name:** Internara – Practical Work Management Information System (SIM-PKL)

**Product Goal:**  
Provide a technology-based platform to manage practical work administration, monitor student
progress, and facilitate mentoring and supervision by instructors, staff, and industry supervisors.

---

## Core Features

### 1. Administrative Management

- Record and store student data, schedules, practical work locations, and related documents
  (reports, permissions, evaluations).
- Centralized and structured data storage for easy access by authorized users.

### 2. Student Progress Monitoring

- Systematic tracking of student progress during practical work.
- Record student competency achievements and activities by instructors.
- Document mentoring content provided by instructors and industry supervisors.
- Monitor student conditions on-site to ensure learning objectives are met.

### 3. Mentoring and Evaluation

- Document all mentoring activities.
- Generate competency achievement reports based on instructor input and mentoring content from
  supervisors.
- Visualize learning outcomes to support decision-making and evaluations.

### 4. Users

- **Instructors:** Input administrative data, record mentoring content, monitor student progress,
  generate competency reports.
- **Practical Work Staff:** Manage schedules, document locations, monitor administration, support
  mentoring documentation.
- **Students:** Access schedules, view competency achievements, review mentoring content.
- **Industry Supervisors:** Provide mentoring content and feedback regarding student performance.

---

## Platform and Technology

- Web-based application accessible via desktop, tablet, and mobile devices.
- Centralized database for storing documents and student information.
- User interface (UI) is intuitive, responsive, and aligned with educational standards.

---

## Scope and Limitations

- System manages only administration, progress monitoring, and mentoring of practical work.
- Evaluation focuses on system feasibility, not operational effectiveness or efficiency.
- Data processed is limited to the participating institution and its industry partners.
- System does not cover curriculum development or practical work outside official scope.

---

## Product Feasibility Criteria

- **End Users (Instructors & Students):** Usability, information access, mentoring content
  recording, and progress monitoring.
- **Staff:** Support for administration, student monitoring, and mentoring documentation.
- **Media Experts:** Design, layout, navigation, and user interaction.
- **Curriculum Experts:** Alignment with curriculum requirements and practical work processes.

---

## Product Benefits

- Integrated platform for managing all aspects of practical work systematically.
- Continuous monitoring of student progress and mentoring documentation.
- Enhanced recording and oversight of mentoring by instructors and industry supervisors, including
  on-site monitoring.
- Serves as a reference for developing similar management systems in other institutions.

---

## Visual Design

### Typography

- Primary Font: Instrument Sans; fallback: standard sans-serif.

### Light Theme

- Background: White and soft gray.
- Text: Soft black.
- Primary Elements: Deep black.
- Accent Color: Emerald green (buttons, links, highlights).
- Main Content: White text/icons on black elements.

### Dark Theme

- Background: Deep black and dark gray.
- Text: Soft white.
- Primary Elements: White.
- Accent Color: Emerald green maintained.
- Main Content: Black text/icons on white elements.

### UI Elements

- Rounded corners: 0.25 – 0.75 rem for inputs, buttons, boxes.
- Border: 1px thin line for main elements.
- Consistent styling for buttons, forms, and menus.
- Color balance ensures readability and reduces visual fatigue.

### Responsiveness

- Mobile-first design.
- Supports multiple screen sizes: mobile, tablet, desktop.
- Supports multi-language (i11n): Indonesian (primary) and English.

---

## Technical Specifications

### Backend

- Framework: Laravel v12 – handles data flow and business logic.

### Frontend

- Livewire v3 + AlpineJS v4 – interactive and reactive interface.

### Database

- SQLite / PostgreSQL / MySQL – stores user data, reports, schedules, and documents.

### Server

- Linux-based VPS environment.

### Integration

- Email notifications via Mailgun or SMTP.

### Security

- Login via email and password.
- Sensitive data encryption.
- Role-based access control (admin, instructor, student, supervisor).

---

## Developer Notice (DO NOT MODIFY)

- **This document is the authoritative specification for the Internara system.** All engineers and
  developers must strictly adhere to the requirements and design outlined herein.
- **No content, feature description, workflow, or technical specification may be altered** without
  formal approval from the project lead. Any deviation from this specification must be documented
  and approved to maintain system integrity and compliance with educational objectives.
