# File Storage App (PDF/DOCX)

Laravel app for uploading PDF/DOCX files, listing them in UI, manual deletion, and automatic expiration cleanup.

## Features

- Upload only `PDF` and `DOCX` files (max 10 MB)
- Store files on local disk with metadata in MySQL
- Manual delete from UI
- Automatic delete after 24 hours
- Notification on delete:
  - RabbitMQ message
  - Email via Mailpit

## Stack

- PHP `8.2`
- Laravel `11`
- MySQL `8`
- RabbitMQ `3-management`
- Nginx
- Mailpit
- Docker Compose

## Environment Setup

1. Copy env file:

```bash
cp .env.example .env
```

1. Configure key values in `.env`:

- App:
  - `APP_URL=http://localhost:8080`
- DB:
  - `DB_HOST=mysql`
  - `DB_DATABASE=filestorage`
  - `DB_USERNAME=filestorage`
  - `DB_PASSWORD=secret`
  - `DB_ROOT_PASSWORD=root`
- RabbitMQ:
  - `RABBITMQ_HOST=rabbitmq`
  - `RABBITMQ_PORT=5672`
  - `RABBITMQ_USER=guest`
  - `RABBITMQ_PASSWORD=guest`
  - `RABBITMQ_QUEUE=file_notifications`
- Email:
  - `NOTIFICATION_EMAIL=admin@example.com`
  - `MAIL_HOST=mailpit`
  - `MAIL_PORT=1025`

1. Optional host port overrides for Docker (to avoid collisions):

- `APP_PORT` (default `8080`)
- `MYSQL_PORT` (default `3306`)
- `RABBITMQ_PORT` (host mapping target for AMQP, default `5672`)
- `RABBITMQ_MANAGEMENT_PORT` (default `15672`)
- `MAILPIT_SMTP_PORT` (default `1025`)
- `MAILPIT_WEB_PORT` (default `8025`)

Example if RabbitMQ ports are already busy on your machine:

```env
RABBITMQ_PORT=5673
RABBITMQ_MANAGEMENT_PORT=15673
```

## Launch Guide (Docker)

1. Build and start:

```bash
docker compose up -d --build
```

1. Install dependencies:

```bash
docker compose exec app composer install
```

1. Generate key:

```bash
docker compose exec app php artisan key:generate
```

1. Run migrations:

```bash
docker compose exec app php artisan migrate
```

1. Open services:

- App: `http://localhost:8080` (or `APP_PORT`)
- RabbitMQ UI: `http://localhost:15672` (or `RABBITMQ_MANAGEMENT_PORT`)
- Mailpit UI: `http://localhost:8025` (or `MAILPIT_WEB_PORT`)

## Services in Compose

- `app`: PHP-FPM Laravel runtime
- `nginx`: HTTP reverse proxy
- `worker`: Laravel queue worker
- `scheduler`: Laravel scheduler (`schedule:work`)
- `mysql`: database
- `rabbitmq`: broker
- `mailpit`: SMTP + inbox UI

Verify all services:

```bash
docker compose ps
```

## Automatic Expiration

- Files expire 24 hours after `uploaded_at`.
- Cleanup command:

```bash
php artisan app:delete-expired-files
```

- In Docker:

```bash
docker compose exec scheduler php artisan app:delete-expired-files
```

- Automatic schedule is configured for every **5 minutes** with overlap protection.

## Notification Flow

On delete (manual or expired):

1. `FileDeleted` event is dispatched.
2. Queue worker handles `SendFileDeletionNotification`.
3. Worker publishes `file.deleted` to RabbitMQ.
4. Worker sends email to `NOTIFICATION_EMAIL` via Mailpit SMTP.

## Testing

Run full test suite:

```bash
php artisan test
```

Run specific suites:

```bash
php artisan test tests/Unit/SchedulerRegistrationTest.php tests/Feature/DeleteExpiredFilesCommandTest.php tests/Feature/RabbitMQNotificationTest.php
```

## Useful Docker Commands

```bash
docker compose logs -f
docker compose down
docker compose down -v
docker compose exec app php artisan pail
```

## Troubleshooting

- `service "scheduler" is not running`: one dependency failed to start; check `docker compose ps` and `docker compose logs`.
- RabbitMQ bind error on `5672` or `15672`: set `RABBITMQ_PORT` / `RABBITMQ_MANAGEMENT_PORT` to free ports in `.env`.
- MySQL Workbench connection:
  - host: `127.0.0.1`
  - port: `3306` (or `MYSQL_PORT`)
  - user/password from `.env`

