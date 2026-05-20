# Data Model: EasyTicket

**Date**: 2026-05-19

---

## Entities

### User

Provided by Laravel's default migration + Breeze. Carries ownership of all domain entities.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| name | VARCHAR(255) | NOT NULL | |
| email | VARCHAR(255) | NOT NULL, UNIQUE | |
| email_verified_at | TIMESTAMP | NULLABLE | Breeze default |
| password | VARCHAR(255) | NOT NULL | Hashed (bcrypt) |
| remember_token | VARCHAR(100) | NULLABLE | |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

---

### Project

A named container for tickets. Belongs to one user.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| user_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | CASCADE DELETE |
| name | VARCHAR(255) | NOT NULL | Min 3, max 255 chars |
| description | TEXT | NULLABLE | |
| color | VARCHAR(7) | NULLABLE | Hex color, e.g. `#FF5733` |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | |

**Indexes**:
- `projects_user_id_index` on `(user_id)`
- `projects_user_id_created_at_index` on `(user_id, created_at DESC)` — for dashboard ordering

**Validation rules**:
- `name`: required, string, min:3, max:255
- `description`: nullable, string, max:5000
- `color`: nullable, string, regex `/^#[0-9A-Fa-f]{6}$/`

**Relationships**:
- `belongsTo(User::class)`
- `hasMany(Ticket::class)`

---

### Ticket

The atomic unit of work. Belongs to a project and indirectly to a user.

| Column | Type | Constraints | Notes |
|--------|------|-------------|-------|
| id | BIGINT UNSIGNED | PK, AUTO_INCREMENT | |
| project_id | BIGINT UNSIGNED | FK → projects.id, NOT NULL | CASCADE DELETE |
| user_id | BIGINT UNSIGNED | FK → users.id, NOT NULL | Creator; supports future multi-user |
| title | VARCHAR(255) | NOT NULL | Min 3, max 255 chars |
| description | LONGTEXT | NULLABLE | |
| status | ENUM | NOT NULL, DEFAULT 'backlog' | See TicketStatus enum |
| priority | ENUM | NOT NULL, DEFAULT 'medium' | See TicketPriority enum |
| due_date | DATE | NULLABLE | |
| created_at | TIMESTAMP | NOT NULL | |
| updated_at | TIMESTAMP | NOT NULL | Tracks last modification (incl. status change) |

**Indexes**:
- `tickets_project_id_index` on `(project_id)`
- `tickets_project_id_status_index` on `(project_id, status)` — board column queries
- `tickets_user_id_index` on `(user_id)`

**SQLite enum note**: SQLite has no native ENUM type. Store as VARCHAR(20) with CHECK constraint,
or use Laravel string cast + PHP Enum. Use PHP `BackedEnum` with string backing; validate at the
Form Request level.

**Validation rules**:
- `title`: required, string, min:3, max:255
- `description`: nullable, string
- `status`: required, in:backlog,todo,in_progress,in_review,done,cancelled
- `priority`: required, in:low,medium,high,critical
- `due_date`: nullable, date, date_format:Y-m-d

**Relationships**:
- `belongsTo(Project::class)`
- `belongsTo(User::class)`

---

## Enums

### TicketStatus (PHP 8.1 Backed Enum)

```php
enum TicketStatus: string
{
    case Backlog    = 'backlog';
    case Todo       = 'todo';
    case InProgress = 'in_progress';
    case InReview   = 'in_review';
    case Done       = 'done';
    case Cancelled  = 'cancelled';

    public function label(): string { ... }
    public function isTerminal(): bool
    {
        return in_array($this, [self::Done, self::Cancelled]);
    }
}
```

**Display order** (left → right on board): Backlog, Todo, In Progress, In Review, Done, Cancelled

---

### TicketPriority (PHP 8.1 Backed Enum)

```php
enum TicketPriority: string
{
    case Low      = 'low';
    case Medium   = 'medium';
    case High     = 'high';
    case Critical = 'critical';

    public function sortOrder(): int
    {
        return match($this) {
            self::Critical => 0,
            self::High     => 1,
            self::Medium   => 2,
            self::Low      => 3,
        };
    }
}
```

---

## Query Patterns

### Board view (tickets grouped by status, sorted)

```php
// Eager-load all tickets for a project, sorted per board column
$project->tickets()
    ->orderByRaw("
        CASE priority
          WHEN 'critical' THEN 0 WHEN 'high' THEN 1
          WHEN 'medium' THEN 2 WHEN 'low' THEN 3
        END,
        CASE WHEN due_date IS NULL THEN 1 ELSE 0 END,
        due_date ASC
    ")
    ->get()
    ->groupBy('status');
```

### Dashboard — project summaries (no N+1)

```php
auth()->user()->projects()
    ->withCount([
        'tickets as total_tickets',
        'tickets as open_tickets' => fn($q) =>
            $q->whereNotIn('status', ['done', 'cancelled']),
        'tickets as overdue_tickets' => fn($q) =>
            $q->whereNotNull('due_date')
              ->where('due_date', '<', now()->startOfDay())
              ->whereNotIn('status', ['done', 'cancelled']),
    ])
    ->orderBy('created_at', 'desc')
    ->get();
```

### Dashboard — attention required (single query)

```php
auth()->user()->tickets()
    ->with('project:id,name,color')
    ->where(fn($q) => $q
        ->where('priority', 'critical')
        ->orWhere(fn($q2) => $q2
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay())
            ->whereNotIn('status', ['done', 'cancelled'])
        )
    )
    ->whereNotIn('status', ['done', 'cancelled'])
    ->orderByRaw("CASE priority WHEN 'critical' THEN 0 ELSE 1 END, due_date ASC NULLS LAST")
    ->limit(20)
    ->get();
```

---

## State Transitions

Status changes are always explicit user actions (drag-and-drop or dropdown). Any status can
transition to any other status in v1 (no enforced linear flow). The `updated_at` timestamp
records the time of each change.

```
Backlog ←→ Todo ←→ In Progress ←→ In Review ←→ Done
                                              ←→ Cancelled
    ↕           ↕         ↕            ↕
  (any status can move to any other status in v1)
```

---

## Migration Plan

1. Laravel default: `create_users_table` (users + password_reset_tokens + sessions)
2. New: `create_projects_table`
3. New: `create_tickets_table`

SQLite-specific setup in `AppServiceProvider::boot()`:
```php
if (DB::getDriverName() === 'sqlite') {
    DB::statement('PRAGMA journal_mode=WAL;');
    DB::statement('PRAGMA foreign_keys=ON;');
}
```
