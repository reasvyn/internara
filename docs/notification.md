# Notification System
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Channel Architecture

Internara delivers notifications through multiple channels, each serving a different purpose.
A single notification can be sent through several channels simultaneously — e.g., a welcome
message is delivered as email (external reach), in-app notification (persistent record), and
broadcast (real-time).

```
Notification sent
    │
    ├── CustomDatabaseChannel ─────► notifications table ──► in-app center
    ├── MailChannel ───────────────► SMTP / SES / Mailgun  ──► email inbox
    ├── BroadcastChannel ─────────► Reverb WebSocket ──────► real-time badge
    └── (future) WebhookChannel ──► external URL ──────────► webhook receiver
```

### Channel Selection by Tier

| Channel | Tier 1 | Tier 2 | Tier 3 |
|---|---|---|---|
| In-app (database) | ✅ | ✅ | ✅ |
| Mail | ✅ (sync) | ✅ (async via queue) | ✅ (async via queue) |
| Broadcast (Reverb) | ❌ | ✅ Optional | ✅ Required |
| Flash messages | ✅ | ✅ | ✅ |

---

## CustomDatabaseChannel (Primary In-App)

The in-app channel stores notifications in a custom `notifications` table. This is the
**canonical record** of all notifications a user has received. It is always used for every
notification.

### Table Schema

```
notifications
├── id              VARCHAR(36)  PRIMARY KEY  — UUID
├── user_id         VARCHAR(36)  FK→users     — recipient
├── type            VARCHAR(50)               — "internship_created", "grade_posted"
├── title           VARCHAR(255)              — Short headline
├── message         TEXT          NULLABLE     — Full message body
├── data            TEXT          NULLABLE     — JSON payload (context, metadata)
├── link            VARCHAR(255)  NULLABLE     — Deep-link URL
├── is_read         BOOLEAN       DEFAULT 0    — Read status
├── read_at         TIMESTAMP     NULLABLE     — When user opened it
└── created_at      TIMESTAMP                 — When it was sent
```

### Notification Class Contract

Each notification class must implement `toCustomDatabase($notifiable)` returning the
structured data array:

```php
public function toCustomDatabase($notifiable): array
{
    return [
        'type' => 'internship_created',
        'title' => 'New Internship Program',
        'message' => 'Summer Internship 2026 has been published.',
        'link' => route('admin.internships.show', $this->internshipId),
        'data' => [
            'internship_id' => $this->internshipId,
            'published_by' => $this->publishedBy,
        ],
    ];
}
```

### Retrieval

```php
// All unread notifications for the current user
$notifications = Notification::forUser(auth()->id())
    ->unread()
    ->latest()
    ->get();

// Mark as read
$notification->markAsRead();
```

---

## Mail Channel

Used for communications that must reach the user outside the application. Mail is always
queued (Tier 2+) or sent synchronously (Tier 1).

| Notification Type | When | Priority |
|---|---|---|
| Welcome email | Account created | High |
| Account locked | 10 failed login attempts | High |
| Password reset | Self-service request | High |
| Recovery code | Admin generates recovery slip | High |
| High-severity incident | CRITICAL severity reported | High |
| Certificate issued | Student receives certificate | Medium |
| Announcement | Admin publishes announcement | Low |

### Driver Matrix

| Driver | Tier 1 | Tier 2 | Tier 3 | Setup Required |
|---|---|---|---|---|
| `log` | ✅ Dev | ❌ | ❌ | None |
| `smtp` | ✅ | ✅ | ✅ | SMTP server credentials |
| `ses` | ❌ | ✅ | ✅ | AWS account, SES verified domain |
| `mailgun` | ❌ | ✅ | ✅ | Mailgun account, domain verification |
| `postmark` | ❌ | ✅ | ✅ | Postmark account, server token |
| `sendmail` | ⚠️ Unreliable | ❌ | ❌ | `sendmail` binary on server |

### SMTP Configuration (Tier 1-2)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-school@gmail.com
MAIL_PASSWORD=app-password
MAIL_ENCRYPTION=tls

MAIL_FROM_ADDRESS=noreply@your-school.sch.id
MAIL_FROM_NAME="${APP_NAME}"
```

**Common SMTP providers for Indonesian schools:**

| Provider | Host | Port | Encryption | Notes |
|---|---|---|---|---|
| Google Workspace | `smtp.gmail.com` | 587 | TLS | Requires app password |
| Microsoft 365 | `smtp.office365.com` | 587 | TLS | |
| Local ISP SMTP | Provider-specific | 465 | SSL | Ask your ISP |
| SendGrid | `smtp.sendgrid.net` | 587 | TLS | Free tier: 100 emails/day |

### SES Configuration (Tier 3)

```env
MAIL_MAILER=ses
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=ap-southeast-1
```

SES requires domain verification and may start in sandbox mode (verified emails only).
Request production access for sending to unverified recipients.

### Development Configuration

```env
# Logs to storage/logs/laravel.log — no email sent
MAIL_MAILER=log

# Optional: preview in browser instead of sending
MAIL_MAILER=preview  # requires mail-preview package
```

### Deliverability Setup

To ensure emails reach recipients (not spam), configure these DNS records for your domain:

```
Record    Type    Value
──────────────────────────────────────
SPF       TXT     v=spf1 include:_spf.google.com ~all
DKIM      TXT     (provided by your email provider)
DMARC     TXT     v=DMARC1; p=quarantine; rua=mailto:dmarc@your-domain
MX        MX      (your email provider's MX record)
```

| Record | Purpose | Risk if Missing |
|---|---|---|
| **SPF** | Authorizes which servers can send from your domain | Email marked as spam or rejected |
| **DKIM** | Cryptographic signature verifying email integrity | Email fails authentication checks |
| **DMARC** | Policy for how receivers handle unauthenticated email | Spoofers can impersonate your domain |
| **Reverse DNS (PTR)** | Maps your mail server IP back to your domain | Some receivers reject unauthenticated IPs |

### Queue Integration

All mail notifications implement `ShouldQueue` for asynchronous delivery:

```php
class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;
    // ...
}
```

In Tier 2+, the queue worker processes mail delivery. In Tier 1 (`QUEUE_CONNECTION=sync`),
mail is sent synchronously during the HTTP request.

### Rate Limiting

Most providers impose sending limits:

| Provider | Daily Limit | Per-Second Limit |
|---|---|---|
| Gmail SMTP | 2,000 | ~5 |
| Microsoft 365 | 10,000 | ~30 |
| SES (production) | 50,000+ | 14/sec (can increase) |
| SendGrid (free) | 100 | ~10 |

To comply with rate limits, use queued notifications with proper backoff:

```php
// config/queue.php
'connections' => [
    'redis' => [
        'driver' => 'redis',
        'queue' => '{default}',
        'retry_after' => 90,
        'block_for' => null,
        'after_commit' => true,
    ],
],
```

### Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Emails not sent | Queue worker not running | Start worker or check Supervisor |
| Emails sent but not received | SPF/DKIM/DMARC not configured | Add DNS records (see above) |
| SMTP authentication failed | Wrong credentials or app password | Generate app-specific password |
| Port blocked | ISP blocks SMTP ports | Use port 587 (TLS) instead of 465 (SSL) |
| Rate limited | Too many emails in short time | Reduce batch size, increase queue delay |
| Emails going to spam | Missing DKIM signature | Configure DKIM on your email provider |
| Connection timeout | SMTP host unreachable | Check firewall, try different port |

---

## Broadcast Channel (Real-Time)

When Laravel Reverb is configured, notifications are pushed to the browser in real time via
WebSocket. The notification bell badge updates and new notifications appear in the list
without polling.

### How It Works

```
App ──broadcast()──► Reverb ──WebSocket──► Browser
                                              │
                                         Echo.listen()
                                              │
                                         Update badge count
                                         Prepend notification
```

### Configuration

```env
# Tier 2+
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=app-id
REVERB_APP_KEY=app-key
REVERB_APP_SECRET=app-secret
REVERB_HOST=0.0.0.0
REVERB_PORT=8080

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### Graceful Degradation

If Reverb is not configured, the broadcast channel is silently skipped. Notifications still
arrive via the in-app channel — the user sees them on the next page load.

---

## Flash Messages

Flash messages provide **instant action feedback** — "Profile updated successfully" or
"Settings saved." They are displayed as toast notifications and disappear after a few
seconds.

```php
flash()->success(__('profile.updated'));
flash()->error(__('settings.save_failed'));
flash()->warning(__('disk_space_low'));
```

| Feature | Flash | In-App Notification |
|---|---|---|
| Persistence | Single request | Until read/pruned |
| Display | Toast, auto-dismiss | Notification center |
| Use case | Action feedback | Event notification |
| Channels | Session only | Database + mail + broadcast |

---

## Queue Integration

Notifications are **always queued** (Tier 2+) or **synchronous** (Tier 1 `QUEUE_CONNECTION=sync`).

| Channel | Tier 1 | Tier 2+ |
|---|---|---|
| CustomDatabaseChannel | Sync (inline) | Async (queue) |
| Mail | Sync (inline) | Async (queue) |
| Broadcast | ❌ Not available | Async (queue) |

Failed notifications are automatically retried (default: 3 attempts). After max retries,
they are stored in `failed_jobs` for manual inspection.

---

## Sending Notifications from Actions

Notifications are sent from Command Actions or listener classes, never directly from
Livewire components.

```php
class NotifyAdminsInternshipCreated implements ShouldQueue
{
    public function handle(InternshipCreated $event): void
    {
        $admins = User::role(['super_admin', 'admin'])->get();

        Notification::send($admins, new InternshipCreatedNotification(
            internshipName: $event->internship->name,
        ));
    }
}
```

For notifications triggered by user action (not event listeners), use the
`SendNotificationAction` which implements the `SendsNotifications` contract:

```php
public function __construct(
    protected readonly SendsNotifications $notifications,
) {}

public function execute(): void
{
    $this->notifications->execute(
        userId: $user->id,
        type: 'account_activated',
        title: 'Account Activated',
        message: 'Your account has been activated.',
        link: route('dashboard'),
    );
}
```

---

## Notification Lifecycle

```
Created (via Action/Event)
    │
    ├── delivered to channels
    │     ├── in-app: stored in notifications table
    │     ├── mail: queued, sent asynchronously
    │     └── broadcast: pushed via WebSocket
    │
    ├── delivered → read by user
    │     └── marked as read (updated_at set)
    │
    └── old → pruned by scheduler
          └── system:cleanup prunes notifications older than retention period
```

### Retention

| Channel | Retention | Pruning |
|---|---|---|
| In-app (database) | 365 days (configurable) | `notifications:prune` via scheduler |
| Mail | N/A (recipient manages) | N/A |
| Broadcast | N/A (real-time only) | N/A |

---

## Where to Find It

- `app/Domain/Core/Channels/CustomDatabaseChannel.php` — custom database channel
- `app/Domain/*/Notifications/` — notification classes organized by domain
- `app/Domain/User/Actions/SendNotificationAction.php` — notification dispatch action
- `app/Domain/Core/Contracts/SendsNotifications.php` — notification contract
- `app/Domain/Admin/Console/Commands/PruneNotificationsCommand.php` — notification pruning
- `config/broadcasting.php` — broadcast driver configuration
- `config/reverb.php` — Reverb WebSocket server configuration
- `config/mail.php` — mail driver and SMTP configuration
- `config/flasher.php` — flash message styling and timeout
- `docs/infrastructure.md` — tier-based infrastructure design
