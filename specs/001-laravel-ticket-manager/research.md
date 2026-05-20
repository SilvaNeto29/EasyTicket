# Research: EasyTicket — Phase 0

**Date**: 2026-05-19
**Feature**: Ticket & Project Management System

---

## 1. Laravel 13 + Livewire Volt Starter Kit

**Decision**: Use Laravel 13 with the official Livewire starter kit (`laravel/breeze` with Livewire
stack, or `composer create-project laravel/laravel` + `php artisan breeze:install livewire`).

**Key findings**:
- Laravel 13 requires PHP 8.2+ (8.3 recommended); ships with Pest pre-configured in Breeze
- Livewire Volt provides single-file components in `resources/views/livewire/` with `<?php` blocks
- Breeze Livewire stack installs: Livewire 3, Alpine.js, Tailwind CSS 3, Vite
- Auth scaffolding includes: login, register, password reset, email verification, profile
- The `@volt` / function-based Volt API is idiomatic for Laravel 13; class-based Livewire is still
  supported but Volt is cleaner for single-file pages

**Rationale**: Breeze + Livewire is the official supported starter — no custom auth needed,
satisfies Constitution Principle IV.

**Alternatives considered**: Inertia/React (rejected: adds JS complexity for a single-user tool),
Filament (rejected: admin-panel framework, over-engineered for this scope).

---

## 2. SQLite Configuration for Laravel

**Decision**: SQLite with WAL (Write-Ahead Logging) mode and foreign key enforcement enabled.

**Key findings**:
- Laravel 13 ships with SQLite support out of the box (PDO SQLite extension)
- WAL mode must be enabled via `database.php` connection options or a `DB::statement` in a seeder/boot
- Foreign key enforcement: must explicitly enable with `PRAGMA foreign_keys = ON` (not on by default in SQLite)
- Laravel's `DatabaseTransactions` trait in Pest Feature tests wraps each test in a transaction → fast test isolation without truncating tables
- SQLite file location: `database/database.sqlite` (standard Laravel location)
- For Docker: SQLite file should live in a named volume to persist across container restarts

**Configuration additions required**:
```php
// config/database.php — sqlite connection
'options' => [
    PDO::ATTR_TIMEOUT => 10,
],
// Enable WAL and FK in AppServiceProvider::boot()
DB::statement('PRAGMA journal_mode=WAL;');
DB::statement('PRAGMA foreign_keys=ON;');
```

**Rationale**: WAL mode allows concurrent reads during writes (relevant if export runs during
normal use). FK enforcement prevents orphaned tickets if CASCADE is misconfigured.

**Alternatives considered**: MySQL/PostgreSQL (rejected: adds Docker service, no benefit at single-user scale).

---

## 3. SortableJS + Livewire 3 Drag-and-Drop

**Decision**: SortableJS via NPM with Livewire's `$wire.dispatch` or `$wire.call` for server sync
on drag-end. Touch support is native in SortableJS (no extra plugin needed in v2+).

**Key findings**:
- SortableJS v1.15+ has built-in touch event support (`touchStartThreshold` option)
- Integration pattern with Livewire 3:
  ```js
  Sortable.create(el, {
    group: columnId,
    onEnd: ({ item, to }) => {
      $wire.dispatch('ticket-status-changed', {
        ticketId: item.dataset.ticketId,
        newStatus: to.dataset.status
      });
    }
  });
  ```
- Livewire component listens via `#[On('ticket-status-changed')]` and calls `UpdateTicketStatus` action
- No need for `livewire-sortable` package — direct SortableJS + dispatch is simpler and more stable
- For mobile: `touchStartThreshold: 5` prevents accidental drags on scroll
- Board columns share the same `group` name so cards can be dragged between columns

**Rationale**: Direct SortableJS integration is simpler than using a Livewire wrapper package
(fewer dependencies, no compatibility issues between Livewire 3 and third-party packages).

**Alternatives considered**: livewire-sortable (rejected: outdated for Livewire 3), Vue Draggable
(rejected: requires Vue.js).

---

## 4. Pest 3 + pest-plugin-laravel Setup

**Decision**: Install Pest 3 with pest-plugin-laravel. Use `RefreshDatabase` trait on Feature tests.
Organize tests mirroring the Action/Model structure.

**Key findings**:
- Laravel Breeze with Livewire stack installs Pest by default in Laravel 13
- If not: `composer require pestphp/pest pestphp/pest-plugin-laravel --dev`
- `php artisan pest:install` configures `tests/Pest.php` with `uses(Tests\TestCase::class)->in('Feature')`
- Feature test pattern for authorization:
  ```php
  it('prevents unauthenticated access to projects', function () {
      get(route('projects.index'))->assertRedirect(route('login'));
  });
  it('rejects creating a project with an empty name', function () {
      $user = User::factory()->create();
      actingAs($user)
          ->post(route('projects.store'), ['name' => ''])
          ->assertSessionHasErrors('name');
  });
  ```
- Use `Pest.php` to set up global `uses()` for `RefreshDatabase` in Feature tests
- Unit tests: no `RefreshDatabase`, no HTTP, pure PHP class instantiation
- `DatabaseTransactions` preferred over `RefreshDatabase` when possible (faster — no migration re-run)

**Adversarial test patterns**:
- Cross-user access: create resource as User A, attempt to access/modify as User B → expect 403/404
- Boundary values: title = '' (empty), title = str_repeat('a', 256) (over 255)
- Null optionals: due_date = null, description = null
- Invalid enum values: status = 'invalid_status', priority = 'ultra_critical'
- Cascade: delete project → verify all tickets deleted
- Auth boundaries: every mutating route tested without auth → 302 redirect to login

**Rationale**: Pest's functional syntax (`it(...)`) produces test descriptions that read like
acceptance criteria from the spec, making test intent obvious.

**Alternatives considered**: PHPUnit class-based (rejected: user preference for Pest).

---

## 5. Docker Compose Setup for Laravel 13

**Decision**: Two-service Docker Compose: `app` (PHP 8.3-FPM + Nginx) + `vite` (optional, for
local asset development). SQLite file in a named Docker volume.

**Key findings**:
- Base image: `php:8.3-fpm-alpine` (small, has PDO SQLite available via `docker-php-ext-install pdo_sqlite`)
- Required PHP extensions: `pdo_sqlite`, `mbstring`, `xml`, `ctype`, `fileinfo`, `tokenizer`, `bcmath`
- Nginx config: standard Laravel config (`root /var/www/html/public`, `try_files $uri $uri/ /index.php`)
- Storage and DB directories must be writable: `chmod -R 775 storage bootstrap/cache`
- Volume mounts:
  - `./:/var/www/html` (app code)
  - `sqlite_data:/var/www/html/database` (SQLite persistence)
- Startup sequence: `php artisan migrate --force` then `php artisan serve` or Nginx
- Port: 8080 → 80 (avoids root port requirement)
- `.env` loaded from `docker-compose.yml` `env_file` directive (never committed)
- `Makefile` convenience targets: `make up`, `make down`, `make shell`, `make test`

**docker-compose.yml key structure**:
```yaml
services:
  app:
    build: .
    ports: ["8080:80"]
    volumes:
      - .:/var/www/html
      - sqlite_data:/var/www/html/database
    env_file: .env
volumes:
  sqlite_data:
```

**Rationale**: Single-service (app + nginx in one container) keeps Docker configuration minimal.
Named volume for SQLite ensures data persists across `docker compose down` + `up` cycles.

**Alternatives considered**: Laravel Sail (rejected: over-engineered, installs MySQL by default,
requires Docker SDK within container), FrankenPHP (newer, less documented for SQLite).

---

## 6. Ticket Priority Sorting Implementation

**Decision**: PHP Enum `TicketPriority` with an integer `order()` method; Eloquent query uses
`orderByRaw` with CASE expression for correct DB-level sorting.

**Sorting SQL**:
```sql
ORDER BY
  CASE priority
    WHEN 'critical' THEN 0
    WHEN 'high'     THEN 1
    WHEN 'medium'   THEN 2
    WHEN 'low'      THEN 3
  END ASC,
  CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC,
  due_date ASC
```

**Eloquent scope**:
```php
public function scopeOrdered(Builder $query): Builder
{
    return $query->orderByRaw("
        CASE priority
          WHEN 'critical' THEN 0 WHEN 'high' THEN 1
          WHEN 'medium' THEN 2 WHEN 'low' THEN 3
        END,
        CASE WHEN due_date IS NULL THEN 1 ELSE 0 END,
        due_date ASC
    ");
}
```

**Unit test**: `TicketPrioritySortTest` verifies sort order with mixed priorities and null due dates.

---

## 7. Overdue Detection

**Decision**: Computed property on `Ticket` model + query scope. No stored `is_overdue` column.

```php
// Model computed property
public function getIsOverdueAttribute(): bool
{
    return $this->due_date !== null
        && $this->due_date->isPast()
        && !in_array($this->status, [TicketStatus::Done, TicketStatus::Cancelled]);
}

// Query scope for dashboard/board
public function scopeOverdue(Builder $query): Builder
{
    return $query
        ->whereNotNull('due_date')
        ->where('due_date', '<', now()->startOfDay())
        ->whereNotIn('status', ['done', 'cancelled']);
}
```

**Unit test**: `TicketOverdueTest` covers: past due date + active status (overdue), past due date +
done (not overdue), past due date + cancelled (not overdue), future due date (not overdue), null
due date (never overdue).

---

## 8. JSON Data Export

**Decision**: `ExportUserData` Action generates a nested PHP array → JSON response with file
download headers. No queue, no temporary files — streams directly.

```php
class ExportUserData
{
    public function handle(User $user): array
    {
        return $user->projects()
            ->with(['tickets'])
            ->get()
            ->map(fn($project) => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'color' => $project->color,
                'created_at' => $project->created_at->toISOString(),
                'updated_at' => $project->updated_at->toISOString(),
                'tickets' => $project->tickets->map(fn($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'description' => $t->description,
                    'status' => $t->status->value,
                    'priority' => $t->priority->value,
                    'due_date' => $t->due_date?->toDateString(),
                    'created_at' => $t->created_at->toISOString(),
                    'updated_at' => $t->updated_at->toISOString(),
                ])->all(),
            ])->all();
    }
}
```

Controller response:
```php
return response()->json($data)
    ->header('Content-Disposition', 'attachment; filename="easyticket-export-' . now()->format('Y-m-d') . '.json"');
```

**Unit test**: `ExportUserDataTest` — verifies structure, field completeness, no N+1 (assertQueryCount).
