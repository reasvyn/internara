# Logging and Observability Documentation

## Overview
The application follows a dual-track observability strategy combining standard file-based logging for debugging and Laravel Pulse for real-time system monitoring.

## 1. Standard Logging
Standard logs are handled via Laravel's `Log` facade.

### Log Channels:
- **Daily**: Stored in `storage/logs/laravel.log`. Rotated every 14 days.
- **Security**: (Planned) Dedicated channel for critical security alerts.

### Best Practices:
- Use appropriate levels: `debug` (development), `info` (workflow milestones), `warning` (handled errors), `error` (unexpected failures).
- **Never** log raw PII or credentials.

## 2. Real-time Monitoring (Laravel Pulse)
[Laravel Pulse](https://pulse.laravel.com/) provides a real-time dashboard at `/pulse`.

### Monitored Metrics:
- **System Resource Usage**: CPU, Memory, Storage.
- **Application Performance**: Slow routes, slow queries, and slow jobs.
- **User Activity**: Most active users and top entry points.
- **Cache Integrity**: Cache hit/miss ratios.

## 3. Custom Loggers
The system is configured to support custom Taps and Processors if advanced log filtering (e.g., PII masking in files) is required.
