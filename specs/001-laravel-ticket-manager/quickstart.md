# Quickstart: EasyTicket

**Get the application running locally in under 60 seconds.**

## Prerequisites

- Docker + Docker Compose (v2) installed
- Git

## Steps

```bash
# 1. Clone the repository
git clone https://github.com/SilvaNeto29/EasyTicket.git
cd EasyTicket

# 2. Start the containers (builds on first run — ~60 seconds)
docker compose up -d
```

**Open your browser**: http://localhost:8080

Register an account and start creating projects. The image build handles
dependencies, assets, and migrations automatically.

---

## Development Workflow

```bash
# Start containers
docker compose up -d

# Stop containers
docker compose down

# Rebuild after code changes (Dockerfile/assets)
docker compose up -d --build

# Run tests locally (faster than inside the container)
./vendor/bin/pest

# Run a specific test file
./vendor/bin/pest tests/Feature/Projects/CreateProjectTest.php

# Open a shell inside the container
docker compose exec app sh

# View application logs
docker compose exec app tail -f storage/logs/laravel.log

# Run database migrations (after schema changes)
docker compose exec app php artisan migrate
```

---

## Environment Variables (`.env.example`)

```dotenv
APP_NAME=EasyTicket
APP_ENV=local
APP_KEY=                          # Generated automatically on first start
APP_DEBUG=true
APP_URL=http://localhost:8080

DB_CONNECTION=sqlite
DB_DATABASE=/var/www/html/database/database.sqlite

MAIL_MAILER=log                   # Emails written to log only (v1)

SESSION_DRIVER=database
SESSION_LIFETIME=120

CACHE_STORE=database
QUEUE_CONNECTION=sync
```

---

## Data Management

**Export your data**: Click "Export Data" on the dashboard → downloads a JSON backup file.

**Database location**: stored in Docker volume `sqlite_data` (persists across restarts).

**Reset everything**:
```bash
docker compose exec app php artisan migrate:fresh
```

---

## Validation

To verify the running application meets the spec's success criteria:

- [ ] Create a project in under 30 seconds
- [ ] Add a ticket with priority and due date
- [ ] Move the ticket between status columns (drag on desktop, touch on mobile)
- [ ] Check the dashboard shows the project summary and any overdue/critical tickets
- [ ] Export data and verify the JSON file downloads
- [ ] Resize browser to 375px width — verify no horizontal scroll, columns stack vertically
- [ ] Run `./vendor/bin/pest` — all 121 tests green
