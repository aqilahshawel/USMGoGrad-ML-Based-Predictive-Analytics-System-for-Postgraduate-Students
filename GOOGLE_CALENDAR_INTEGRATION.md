# Google Calendar Integration (Development/Test)

## Purpose
This document explains how calendar OAuth works in this project while keeping system identity separate from calendar identity.

- System login/roles use institutional-style emails in `users.email` (example: `name@student.usm.my`, `name@lecturer.usm.my`).
- Calendar OAuth uses real Gmail test accounts and stores those details in `calendar_integrations`.

## Email Separation Rule
- `users.email`: system identity + role management only.
- `calendar_integrations.calendar_email`: real Gmail account used for Google OAuth and Google Calendar API.
- `calendar_integrations.access_token` and `calendar_integrations.refresh_token`: encrypted token storage for API access.

## Implemented Flow
- Lecturer connects Google account from schedule page.
- OAuth callback stores integration data in `calendar_integrations`.
- Access token is auto-refreshed when near expiry.
- If refresh fails/revoked, integration status becomes `revoked` and reconnect is required.
- Lecturer event creation writes to Google Calendar (if connected) and local `schedules` table.
- Student schedule view resolves to assigned supervisor only.

## Prerequisites
1. Google Cloud OAuth client (Web Application) configured.
2. Redirect URI set to:
   - `http://localhost/calendar/google/callback`
   - or your app URL equivalent.
3. Required PHP extensions:
   - `openssl`
   - `pdo_sqlite`
   - `mbstring`

## Environment Variables
Set these in `.env`:

```env
GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI="${APP_URL}/calendar/google/callback"
```

## Database Setup
Run migrations:

```bash
php artisan migrate
```

This creates `calendar_integrations` for provider-based calendar auth storage.

## Role Behavior
- Lecturer:
  - Can open schedule page.
  - Can connect/disconnect Google Calendar.
  - Can create events (`/save-event`).
- Student:
  - Can open schedule page.
  - Can only view assigned supervisor schedule.
  - Cannot create events.

## Outlook + Google Demo Note
If lecturer schedule originates in Outlook, sync Outlook calendar into the lecturer's Gmail calendar account first (outside Laravel). Laravel reads/writes through Google Calendar API for the connected Gmail account.

## End-to-End Demo Checklist
1. Login as lecturer account (institutional-style email in system).
2. Go to Schedule page.
3. Click `Connect / Reconnect Google` and authenticate with real Gmail test account.
4. Confirm status card shows `connected` and displays calendar email.
5. Create an event on calendar UI.
6. Verify event appears in connected Gmail calendar.
7. Login as student assigned to that lecturer.
8. Open Schedule page and confirm lecturer/supervisor events are visible.
9. Confirm student cannot create events.

## Troubleshooting
- Status `revoked` or disconnected:
  - Reconnect Google from schedule page.
- Events not loading from Google:
  - Check OAuth credentials in `.env`.
  - Ensure redirect URI exactly matches Google Cloud config.
  - Check `storage/logs/laravel.log` for token/API errors.
- Artisan command errors related to `mb_strimwidth`:
  - Enable PHP `mbstring` extension in active CLI `php.ini` and restart terminal.
