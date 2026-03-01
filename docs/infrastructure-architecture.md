# Infrastructure Architecture: System Hosting & Scaling

This document provides the high-level **Infrastructure Architecture** for the Internara system, standardized according to **ISO/IEC 42010**.

---

## 1. Architectural Style: Cloud-Native Monolith

Internara is optimized for a cloud-native deployment model where the application remains a monolith, but its supporting services are decoupled for scalability and resilience.

---

## 2. Infrastructure Components

### 2.1 Compute Layer
- **Web Tier**: PHP 8.4-FPM managed by Nginx.
- **Worker Tier**: Persistent background processes (Supervisor) executing Laravel Queues.

### 2.2 Persistence Layer
- **Relational DB**: PostgreSQL or MySQL (ACID compliant for transactional integrity).
- **In-Memory Cache**: Redis (For session state, setting cache, and queue management).
- **Object Storage**: S3-compatible private disk for forensic evidence and reports.

---

## 3. Connectivity & Security

- **Load Balancer**: Manages SSL termination (TLS 1.3) and traffic distribution.
- **Private Subnet**: The database and cache must reside in a private network, inaccessible from the public internet.
- **WAF**: Integration with Cloudflare (Honeypot/Turnstile) for automated perimeter defense.

---

## 4. Disaster Recovery (ISO/IEC 27001)

- **Backups**: Automated daily snapshots of the database and object storage.
- **RTO/RPO**: Recovery Time Objective ≤ 4 hours; Recovery Point Objective ≤ 24 hours.
- **Immutable Artifacts**: Production releases are promoted as immutable Git Tags.
