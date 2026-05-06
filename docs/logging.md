# Logging & Observability

## Application Logs

Laravel's `Log` facade with daily rotation (14 days). Stored in `storage/logs/`.

| Level | Use case |
|---|---|
| `debug` | Development diagnostics |
| `info` | Workflow milestones |
| `warning` | Handled errors |
| `error` | Unexpected failures |

Never log raw PII or credentials.

## Laravel Pulse

Real-time dashboard at `/pulse` monitors:

- System resources (CPU, memory, storage)
- Slow routes, queries, and jobs
- Most active users and entry points
- Cache hit/miss ratios

Configured via `config/pulse.php`. Authorization gate controls access.

## Custom Loggers

Custom Taps and Processors can be added for advanced filtering (e.g., PII masking) when needed.
