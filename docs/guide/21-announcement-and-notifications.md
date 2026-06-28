# Chapter 21: Announcement & Notifications

> **Last updated:** 2026-06-16
> **Changes:** sync — initial metadata sync with new format

## Description
This chapter covers two communication features: **Announcements** (broadcast messages sent by
administrators) and **Notifications** (personal alerts received by users).

---


## 21.1 Announcements Overview

Announcements are broadcast messages sent to all users or targeted to specific roles. They support
Markdown content, scheduling, and delivery via email, in-app notification, and broadcast.

| Role | What They Can Do |
|------|------------------|
| **Admin** | Create, schedule, publish, and delete announcements |
| **All Users** | Receive and read announcements |

### 21.1.1 Announcement Status Lifecycle

```
Draft ── Publish ──> Published
  │
  └── Schedule ──> Scheduled ──> Published (auto at scheduled time)
```

| Status | Meaning |
|--------|---------|
| **Draft** | Being written, not yet sent |
| **Scheduled** | Will be sent automatically at the scheduled date/time |
| **Published** | Sent to all targeted users |

---

## 21.2 Managing Announcements (Admin)

Navigate to **System → Announcements** from the admin sidebar.

### 21.2.1 Creating an Announcement

1. Click **New Announcement**
2. Fill in the fields:

| Field | Description | Example |
|-------|-------------|---------|
| **Title** | Announcement subject line | PKL Schedule Update |
| **Message** | Announcement body (Markdown supported) | The final report deadline has been extended to **30 June**. |
| **Type** | Visual indicator | Info, Success, Warning, Error |
| **Link** | Optional link for "View Details" | https://internara.example/guide |

3. Choose a delivery option:

| Option | Description |
|--------|-------------|
| **Save as Draft** | Save without sending. You can publish later. |
| **Schedule** | Set a future date/time for automatic publishing |
| **Publish Now** | Send immediately to all targeted users |

4. Choose the target audience:

| Option | Description |
|--------|-------------|
| **All Users** | Send to every user in the system |
| **Specific Roles** | Select specific roles (students, teachers, supervisors, admins) |

5. Click **Send**

### 21.2.2 Scheduled Announcements

Scheduled announcements are published automatically by the scheduler:

```bash
php artisan announcements:publish
```

This command runs every minute via the cron scheduler. When a scheduled announcement's time
arrives, it is automatically published and delivered to all targeted users.

### 21.2.3 Publishing a Draft

1. Find the draft announcement in the list
2. Click the **Publish** button (paper airplane icon)
3. Confirm — the announcement is sent immediately

### 21.2.4 Deleting an Announcement

Click **Delete** on any announcement to remove it. Only announcements you created can be deleted.

---

## 21.3 Notifications Overview

Notifications are personal alerts delivered to individual users. They appear as a bell icon in the
header and in the full Notification Center.

### 21.3.1 Notification Types

| Type | Triggered By | Delivered To |
|------|--------------|--------------|
| **Welcome** | New account created | The new user |
| **Announcement** | Admin publishes announcement | All / targeted users |
| **Incident Reported** | High/Critical incident submitted | All admins |
| **Assignment Published** | Teacher publishes assignment | Enrolled students |
| **Submission Feedback** | Teacher grades submission | The submitting student |
| **Internship Registration** | Student registers | The student |
| **Backup Failed** | System backup fails | Super admins |

---

## 21.4 Notification Bell

The notification bell in the top navigation bar shows your unread notification count:

- **No badge** — no unread notifications
- **Red badge with number** — shows how many unread notifications you have
- Click the bell to go to the full Notification Center

The unread count is cached and updated automatically when you read or receive notifications.

---

## 21.5 Notification Center

Navigate to **Notifications** from the sidebar, or click the bell icon in the header.

### 21.5.1 Viewing Notifications

The notification center shows a paginated list of your notifications with:

- **Title and message** for each notification
- **Timestamp** showing when it was received
- **Read/Unread status** — unread notifications are visually distinct

### 21.5.2 Reading a Notification

1. Click on a notification to view its full details
2. The notification is automatically marked as **Read**
3. If the notification has a link, click it to navigate to the relevant page

### 21.5.3 Marking Notifications as Read

| Action | How |
|--------|-----|
| **Mark One as Read** | Click the notification to view it — automatically marked as read |
| **Mark Selected as Read** | Select multiple notifications and click **Mark as Read** |
| **Mark All as Read** | Click **Mark All as Read** to clear all unread notifications at once |

### 21.5.4 Filtering Notifications

Use the filter dropdown to show:
- **All** — all notifications (default)
- **Unread** — only notifications you haven't read yet
- **Read** — only notifications you have already read

### 21.5.5 Deleting Notifications

Select multiple notifications and choose **Delete Selected** to remove them. This action cannot
be undone.

---

## 21.6 Troubleshooting

### Announcement not received

- Check that the announcement status is **Published** (not Draft or Scheduled)
- If targeted to specific roles, verify you have one of the selected roles
- Check your email spam folder for email notifications
- Verify the queue worker is running: `php artisan queue:work`

### Scheduled announcement not sent

- Ensure the cron scheduler is running: `* * * * * php artisan schedule:run`
- Verify the `announcements:publish` command runs: check the scheduler output
- Check the scheduled time has passed

### Notification bell shows wrong count

The unread count is cached for 60 seconds. Wait a moment and refresh the page. If the count is
still wrong, it will update automatically when you view the Notification Center.

### Email notifications not arriving

- Check that mail settings are configured in **System Settings → Mail**
- Send a test email from the Settings page to verify configuration
- Check the queue worker is running
- Check your spam/junk folder

---

**← Previous: [Chapter 20: Evaluation & Incident](20-evaluation-and-incident.md)**
**Next: [Chapter 22: System Observability](22-system-observability.md)**
