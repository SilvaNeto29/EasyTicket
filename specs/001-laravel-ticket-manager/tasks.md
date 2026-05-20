---
description: "Task list for EasyTicket — Ticket & Project Management System"
---

# Tasks: EasyTicket — Ticket & Project Management System

**Input**: Design documents from `specs/001-laravel-ticket-manager/`

**Prerequisites**: plan.md ✅ | spec.md ✅ | research.md ✅ | data-model.md ✅ | contracts/routes.md ✅

**Tests**: MANDATORY (TDD). Every feature phase includes Pest tests written and FAILING before implementation. Tests MUST cover adversarial cases, not only happy paths.

**Frontend**: UI implementation tasks MUST use the `/frontend-design` skill for all Livewire + Blade components.

**Organization**: Tasks grouped by phase; user story phases are independently testable increments.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies on other incomplete tasks)
- **[Story]**: Which user story this task belongs to (US0=Auth, US1=Projects, US2=Tickets, US3=Board, US4=Dashboard)
- File paths are relative to repository root

---

## Phase 1: Setup

**Purpose**: Scaffold the Laravel 13 project and development environment. All tasks must
complete before any business logic work begins.

- [ ] T001 Scaffold Laravel 13 project in current directory: `composer create-project laravel/laravel . "^13.0"` and verify `php artisan --version`
- [ ] T002 [P] Create `Dockerfile` using `php:8.3-fpm-alpine` with required extensions (pdo_sqlite, mbstring, xml, ctype, fileinfo, tokenizer, bcmath) and Nginx config
- [ ] T003 [P] Create `docker-compose.yml` with `app` service, port 8080→80, named volume `sqlite_data` for `database/` directory, and `env_file: .env`
- [ ] T004 [P] Create `docker/nginx.conf` with standard Laravel Nginx configuration (`root /var/www/html/public`, `try_files` for PHP-FPM)
- [ ] T005 Install Livewire Breeze starter kit (Livewire stack): `php artisan breeze:install livewire` — installs Livewire 3, Alpine.js, Tailwind CSS 3, Vite, and auth scaffolding
- [ ] T006 Install Pest 3 with pest-plugin-laravel: `composer require pestphp/pest pestphp/pest-plugin-laravel --dev` then `php artisan pest:install` to configure `tests/Pest.php`
- [ ] T007 [P] Install SortableJS via npm: `npm install sortablejs` — required for kanban drag-and-drop
- [ ] T008 Configure SQLite in `.env` (`DB_CONNECTION=sqlite`, `DB_DATABASE=/absolute/path/to/database/database.sqlite`), create `database/database.sqlite`, and update `config/database.php` sqlite options
- [ ] T009 Create `.env.example` with all required variables documented (APP_KEY blank, DB_DATABASE path, MAIL_MAILER=log, SESSION/CACHE drivers)
- [ ] T010 Add SQLite WAL mode and foreign key enforcement in `app/Providers/AppServiceProvider.php` `boot()` method using `DB::statement('PRAGMA journal_mode=WAL;')` and `PRAGMA foreign_keys=ON;`

**Checkpoint**: `docker compose up -d` starts successfully; `php artisan test` runs (zero tests pass — expected).

---

## Phase 2: Foundational

**Purpose**: Shared infrastructure required by ALL user stories. MUST be complete before any
user story work begins.

**⚠️ CRITICAL**: No user story work can start until this phase is complete.

### Tests for Foundation ⚠️ Write FIRST — must FAIL before implementation

- [ ] T011 [P] Write Unit tests for `TicketStatus` enum in `tests/Unit/Models/TicketStatusTransitionTest.php`: verify all 6 cases exist, `isTerminal()` returns true only for Done/Cancelled, label strings are non-empty
- [ ] T012 [P] Write Unit tests for `TicketPriority` enum in `tests/Unit/Models/TicketPrioritySortTest.php`: verify `sortOrder()` returns 0 for Critical through 3 for Low, verify sort order produces Critical→High→Medium→Low sequence
- [ ] T013 Write Unit tests for Ticket overdue detection in `tests/Unit/Models/TicketOverdueTest.php`: past due + active status = overdue; past due + Done = not overdue; past due + Cancelled = not overdue; future due = not overdue; null due = never overdue

### Implementation

- [ ] T014 [P] Create `TicketStatus` backed string enum in `app/Enums/TicketStatus.php` with cases: Backlog, Todo, InProgress, InReview, Done, Cancelled — including `label()` and `isTerminal()` methods
- [ ] T015 [P] Create `TicketPriority` backed string enum in `app/Enums/TicketPriority.php` with cases: Low, Medium, High, Critical — including `sortOrder(): int` method
- [ ] T016 Create `create_projects_table` migration in `database/migrations/` with columns: id, user_id (FK→users CASCADE), name (varchar 255), description (text nullable), color (varchar 7 nullable), timestamps — plus indexes on (user_id) and (user_id, created_at)
- [ ] T017 Create `create_tickets_table` migration in `database/migrations/` with columns: id, project_id (FK→projects CASCADE), user_id (FK→users), title (varchar 255), description (longtext nullable), status (string 20 default 'backlog'), priority (string 10 default 'medium'), due_date (date nullable), timestamps — plus indexes on (project_id), (project_id, status), (user_id)
- [ ] T018 Run `php artisan migrate` and verify both tables are created with correct schema
- [ ] T019 Create `Project` model in `app/Models/Project.php` with: `$fillable`, `belongsTo(User)`, `hasMany(Ticket)`, `withCount` scopes for total/open/overdue tickets
- [ ] T020 Create `Ticket` model in `app/Models/Ticket.php` with: `$fillable`, `$casts` (status→TicketStatus, priority→TicketPriority, due_date→date), `belongsTo(Project)`, `belongsTo(User)`, `getIsOverdueAttribute()` computed property, `scopeOrdered()` with priority CASE ORDER BY + null due_date last, `scopeOverdue()`
- [ ] T021 Add `hasMany(Project)` and `hasMany(Ticket)` relationships to `app/Models/User.php`
- [ ] T022 [P] Create `StoreProjectRequest` in `app/Http/Requests/StoreProjectRequest.php` with rules: name (required, string, min:3, max:255), description (nullable, string, max:5000), color (nullable, string, regex hex)
- [ ] T023 [P] Create `UpdateProjectRequest` in `app/Http/Requests/UpdateProjectRequest.php` (same rules as Store)
- [ ] T024 [P] Create `StoreTicketRequest` in `app/Http/Requests/StoreTicketRequest.php` with rules: project_id (required, exists:projects,id), title (required, min:3, max:255), description (nullable), status (required, in enum values), priority (required, in enum values), due_date (nullable, date, Y-m-d format)
- [ ] T025 [P] Create `UpdateTicketRequest` in `app/Http/Requests/UpdateTicketRequest.php` with rules: title (required, min:3, max:255), description (nullable), priority (required, in enum), due_date (nullable, date)
- [ ] T026 Create base authenticated layout in `resources/views/components/layouts/app.blade.php` — mobile-first responsive nav with project link, dashboard link, logout; **use `/frontend-design` skill**
- [ ] T027 Configure `routes/web.php` with auth+verified middleware group wrapping all application routes; add redirect from `/` to `/dashboard`

**Checkpoint**: Migrations applied, Enums created, Models with relationships functional, Requests with validation rules, base layout renders at all screen sizes. All Unit tests pass (T011-T013).

---

## Phase 3: User Story 0 — Authentication (P0 Foundation)

**Goal**: Verify Breeze auth scaffolding is fully functional and secure; establish auth test baselines used by all later phases.

**Independent Test**: A fresh browser session is blocked from all protected pages; login/logout cycle works correctly; rate limiting triggers after 5 failed attempts.

### Tests for US0 ⚠️ Write FIRST — must FAIL before implementation

- [ ] T028 [US0] Write Feature tests for authentication in `tests/Feature/Auth/AuthenticationTest.php`: (a) unauthenticated GET /dashboard → redirect to /login; (b) unauthenticated GET /projects → redirect to /login; (c) POST /login with valid credentials → redirect to /dashboard; (d) POST /login with invalid credentials → session has errors, no session created; (e) POST /logout → session destroyed, redirect to /login; (f) 5 failed logins → 429 rate limit response

### Implementation (Breeze already scaffolded — verify + configure)

- [ ] T029 [US0] Verify Breeze Livewire auth views exist and are mobile-friendly in `resources/views/livewire/` (login, register, forgot-password, reset-password); apply `/frontend-design` skill for styling polish
- [ ] T030 [US0] Confirm all protected routes return 302 redirect for unauthenticated requests (review middleware in `routes/web.php` from T027)

**Checkpoint**: All auth Feature tests (T028) pass. Green bar before proceeding.

---

## Phase 4: User Story 1 — Manage Projects (Priority: P1) 🎯 MVP

**Goal**: User can create, list, edit, and delete projects. Each project shows ticket count summary.

**Independent Test**: With no tickets in the system, a user can: see project list, create a project, edit it, delete it with cascade warning. All project routes block unauthenticated access.

### Tests for US1 ⚠️ Write FIRST — must FAIL before implementation

- [ ] T031 [P] [US1] Write Feature tests for project creation in `tests/Feature/Projects/CreateProjectTest.php`: (a) authenticated user creates project with valid data → 201/redirect, project in DB; (b) empty name → validation error; (c) name > 255 chars → validation error; (d) name < 3 chars → validation error; (e) invalid hex color → validation error; (f) unauthenticated POST → redirect to login; (g) creates project with null description/color → succeeds
- [ ] T032 [P] [US1] Write Feature tests for project update in `tests/Feature/Projects/UpdateProjectTest.php`: (a) valid update → persisted; (b) empty name → validation error; (c) authenticated user cannot update another user's project → 403/404; (d) nonexistent project ID → 404
- [ ] T033 [P] [US1] Write Feature tests for project deletion in `tests/Feature/Projects/DeleteProjectTest.php`: (a) delete project with no tickets → removed from DB; (b) delete project with tickets → all tickets also removed (cascade); (c) cannot delete another user's project → 403/404; (d) unauthenticated DELETE → redirect to login

### Implementation

- [ ] T034 [P] [US1] Create `CreateProject` Action in `app/Actions/Projects/CreateProject.php`: accepts `User $user, string $name, ?string $description, ?string $color` — creates and returns `Project`
- [ ] T035 [P] [US1] Create `UpdateProject` Action in `app/Actions/Projects/UpdateProject.php`: accepts `Project $project, string $name, ?string $description, ?string $color` — updates and returns `Project`
- [ ] T036 [P] [US1] Create `DeleteProject` Action in `app/Actions/Projects/DeleteProject.php`: accepts `Project $project` — deletes (DB CASCADE handles tickets)
- [ ] T037 [US1] Create `projects.index` Livewire component in `resources/views/livewire/projects/index.blade.php`: loads user's projects with `withCount` aggregates (total, open, overdue tickets), delete confirmation modal; **use `/frontend-design` skill**
- [ ] T038 [US1] Create `projects.create` Livewire component in `resources/views/livewire/projects/create.blade.php`: form with name, description, color picker; calls `CreateProject` action on save; **use `/frontend-design` skill**
- [ ] T039 [US1] Create `projects.edit` Livewire component in `resources/views/livewire/projects/edit.blade.php`: pre-filled form; calls `UpdateProject` action; **use `/frontend-design` skill**
- [ ] T040 [US1] Register project routes in `routes/web.php`: GET /projects, GET /projects/create, POST /projects, GET /projects/{project}/edit, PUT /projects/{project}, DELETE /projects/{project}

**Checkpoint**: All US1 Feature tests (T031-T033) pass. Project CRUD fully functional. Unauthenticated requests blocked.

---

## Phase 5: User Story 2 — Create and Track Tickets (Priority: P2)

**Goal**: User can create tickets inside a project with title, description, priority, status, and optional due date; view ticket detail; edit; delete. Overdue tickets are visually flagged.

**Independent Test**: Inside an existing project, a user can create a ticket, view its detail page, edit any field including back-dating the due date (flagged as overdue immediately), and delete it.

### Tests for US2 ⚠️ Write FIRST — must FAIL before implementation

- [ ] T041 [P] [US2] Write Feature tests for ticket creation in `tests/Feature/Tickets/CreateTicketTest.php`: (a) valid ticket created → in DB under correct project; (b) empty title → validation error; (c) title < 3 chars → validation error; (d) title > 255 chars → validation error; (e) invalid priority value → validation error; (f) invalid status value → validation error; (g) project_id of another user's project → 403/404; (h) null due_date → succeeds; (i) past due_date → succeeds, ticket.is_overdue = true; (j) unauthenticated → redirect to login
- [ ] T042 [P] [US2] Write Feature tests for ticket update in `tests/Feature/Tickets/UpdateTicketTest.php`: (a) valid update persists; (b) title empty → error; (c) invalid priority → error; (d) cannot update another user's ticket → 403/404; (e) update due_date to past → is_overdue becomes true; (f) update status to Done → is_overdue becomes false regardless of due_date
- [ ] T043 [P] [US2] Write Feature tests for ticket deletion in `tests/Feature/Tickets/DeleteTicketTest.php`: (a) deletes ticket from DB; (b) cannot delete another user's ticket → 403/404; (c) nonexistent ticket ID → 404; (d) unauthenticated → redirect

### Implementation

- [ ] T044 [P] [US2] Create `CreateTicket` Action in `app/Actions/Tickets/CreateTicket.php`: accepts Project, User, title, description, TicketPriority, TicketStatus, ?Carbon due_date — creates and returns Ticket
- [ ] T045 [P] [US2] Create `UpdateTicket` Action in `app/Actions/Tickets/UpdateTicket.php`: accepts Ticket, array data (title, description, priority, due_date) — updates and returns Ticket
- [ ] T046 [P] [US2] Create `DeleteTicket` Action in `app/Actions/Tickets/DeleteTicket.php`: accepts Ticket — deletes
- [ ] T047 [US2] Create `tickets.create` Livewire component in `resources/views/livewire/tickets/create.blade.php`: form with title, description (textarea), priority select, status select, due_date datepicker; receives `$projectId` from query param; calls `CreateTicket` action; **use `/frontend-design` skill**
- [ ] T048 [US2] Create `tickets.show` Livewire component in `resources/views/livewire/tickets/show.blade.php`: displays all ticket fields, inline-edit toggle, status dropdown, overdue badge, delete with confirmation; calls `UpdateTicket`/`DeleteTicket` actions; **use `/frontend-design` skill**
- [ ] T049 [US2] Register ticket routes in `routes/web.php`: GET /tickets/create, POST /tickets, GET /tickets/{ticket}, PUT /tickets/{ticket}, DELETE /tickets/{ticket}

**Checkpoint**: All US2 Feature tests (T041-T043) pass. Ticket CRUD functional. Overdue detection works via `is_overdue` attribute.

---

## Phase 6: User Story 3 — Manage Ticket Workflow / Kanban Board (Priority: P3)

**Goal**: Kanban board view per project with 6 status columns. Tickets sorted by priority then due date within each column. Touch-aware drag-and-drop to change status. Filter by priority / overdue.

**Independent Test**: Opening a project with tickets in various statuses shows the kanban board with correct column grouping and sort order. Dragging a card persists the status change. Filter reduces visible cards without breaking column structure.

### Tests for US3 ⚠️ Write FIRST — must FAIL before implementation

- [ ] T050 [P] [US3] Write Feature tests for ticket status update in `tests/Feature/Tickets/UpdateTicketStatusTest.php`: (a) valid status change persists; (b) invalid status string → 422 validation error; (c) cannot change status of another user's ticket → 403/404; (d) unauthenticated → redirect; (e) status change updates updated_at timestamp
- [ ] T051 [P] [US3] Write Feature tests for kanban board in `tests/Feature/Board/KanbanBoardTest.php`: (a) board loads all 6 columns; (b) tickets appear under correct status column; (c) within column, Critical tickets appear before High before Medium before Low; (d) within same priority, earlier due_date appears first; (e) tickets with null due_date appear last within priority group; (f) filter by priority=critical shows only critical tickets across all columns; (g) unauthenticated access → redirect; (h) cannot view another user's project board → 403/404

### Implementation

- [ ] T052 [US3] Create `UpdateTicketStatus` Action in `app/Actions/Tickets/UpdateTicketStatus.php`: accepts Ticket, TicketStatus — validates status is a valid enum case, updates status, touches updated_at, returns Ticket
- [ ] T053 [US3] Create `projects.show` (Kanban Board) Livewire component in `resources/views/livewire/projects/show.blade.php`: loads all project tickets with `scopeOrdered()`, groups by status into 6 columns, renders filter controls (priority, overdue toggle), dispatches `#[On('ticket-status-changed')]` listener calling `UpdateTicketStatus` action; **use `/frontend-design` skill**
- [ ] T054 [US3] Implement SortableJS initialization in `resources/js/app.js`: initialize Sortable on each column element with `group:'kanban'` and touch support, `onEnd` callback dispatches Livewire event `ticket-status-changed` with `{ticketId, newStatus}`
- [ ] T055 [US3] Add PUT /tickets/{ticket}/status route in `routes/web.php` for direct status update (used by dropdown on ticket detail page)

**Checkpoint**: All US3 Feature tests (T050-T051) pass. Board renders with correct sorting. Drag persists status. Filter works.

---

## Phase 7: User Story 4 — Dashboard Overview + Export (Priority: P4)

**Goal**: Home dashboard shows all project summary cards (ticket counts), an "Attention Required" section for overdue/critical tickets, and a one-click JSON data export button.

**Independent Test**: Dashboard loads all data in single page visit with no secondary requests. Export button downloads a valid JSON file containing all projects and tickets.

### Tests for US4 ⚠️ Write FIRST — must FAIL before implementation

- [ ] T056 [P] [US4] Write Feature tests for dashboard in `tests/Feature/Dashboard/DashboardTest.php`: (a) authenticated user sees all their projects with correct total/open/overdue counts; (b) overdue ticket appears in attention section; (c) critical priority ticket appears in attention section; (d) done/cancelled tickets do NOT appear in attention section; (e) user sees only their own projects (not another user's); (f) empty state shown when no projects exist; (g) assert query count ≤ 3 (no N+1 — projects query + attention query + counts)
- [ ] T057 [P] [US4] Write Unit tests for `ExportUserData` in `tests/Unit/Actions/ExportUserDataTest.php`: (a) export contains all user's projects; (b) each project contains all its tickets; (c) all required fields present (id, name, description, color, tickets with id/title/description/status/priority/due_date/timestamps); (d) projects from other users NOT included; (e) empty export (no projects) returns empty array
- [ ] T058 [P] [US4] Write Feature tests for export in `tests/Feature/Dashboard/ExportTest.php`: (a) GET /export returns JSON with Content-Disposition: attachment; (b) filename contains today's date (Y-m-d); (c) unauthenticated → redirect to login; (d) export with no data returns valid JSON array `[]`

### Implementation

- [ ] T059 [US4] Create `ExportUserData` Action in `app/Actions/Export/ExportUserData.php`: accepts User, returns nested PHP array of projects with tickets — eager-loads all with single query (`$user->projects()->with('tickets')->get()`)
- [ ] T060 [US4] Create `ExportController` in `app/Http/Controllers/ExportController.php`: calls `ExportUserData` action, returns `response()->json($data)->header('Content-Disposition', 'attachment; filename="easyticket-export-'.now()->format('Y-m-d').'.json"')`
- [ ] T061 [US4] Create `dashboard` Livewire component in `resources/views/livewire/dashboard.blade.php`: mounts project summary cards (with counts via `withCount`), attention section (overdue + critical tickets with project name), empty state, export button; **use `/frontend-design` skill**
- [ ] T062 [US4] Register dashboard and export routes in `routes/web.php`: GET /dashboard → dashboard component, GET /export → ExportController@download

**Checkpoint**: All US4 Feature tests (T056-T058) and Unit tests (T057) pass. Dashboard loads correctly. Export downloads valid JSON.

---

## Phase 8: Polish & Cross-Cutting Concerns

**Purpose**: Final quality pass across all features.

- [ ] T063 [P] Verify mobile-first layout across all pages at 375px viewport: no horizontal scroll, touch targets ≥ 44×44px, board columns stack vertically on mobile — fix any layout issues in Blade/Tailwind classes
- [ ] T064 [P] Add overdue visual badge (red indicator) to ticket cards on the board and to ticket detail view — use computed `is_overdue` attribute, no extra queries
- [ ] T065 [P] Add delete confirmation modal to project deletion (warns about cascade) and ticket deletion — ensure modal is accessible on mobile
- [ ] T066 Run complete Pest test suite: `./vendor/bin/pest --coverage` — fix any failures, verify all phases have passing tests
- [ ] T067 [P] Validate `docker compose up` quickstart: follow `quickstart.md` from scratch, verify all steps work and app is reachable at http://localhost:8080 in under 60 seconds
- [ ] T068 Update `quickstart.md` if any steps changed during implementation

---

## Dependencies & Execution Order

### Phase Dependencies

- **Setup (Phase 1)**: No dependencies — start immediately
- **Foundational (Phase 2)**: Requires Phase 1 complete — BLOCKS all user story phases
- **US0 Auth (Phase 3)**: Requires Phase 2 — provides auth baseline for all feature tests
- **US1 Projects (Phase 4)**: Requires Phase 3 — MVP deliverable
- **US2 Tickets (Phase 5)**: Requires US1 complete (tickets need projects)
- **US3 Board (Phase 6)**: Requires US2 complete (board needs tickets)
- **US4 Dashboard (Phase 7)**: Requires US1+US2 complete (needs projects+tickets data)
- **Polish (Phase 8)**: Requires all user story phases complete

### Within Each User Story Phase

1. Write tests FIRST → confirm they FAIL (red)
2. Create Action classes (parallelizable within a story)
3. Create Livewire components (depend on Actions)
4. Register routes
5. Confirm tests PASS (green)
6. Refactor if needed — tests must stay green

### Parallel Opportunities

- Phase 1: T002, T003, T004, T007, T009 can all run in parallel
- Phase 2: T011, T012 can run in parallel; T014, T015 can run in parallel after; T022, T023, T024, T025 can run in parallel
- Phase 4: T031, T032, T033 (tests) in parallel; T034, T035, T036 (actions) in parallel
- Phase 5: T041, T042, T043 (tests) in parallel; T044, T045, T046 (actions) in parallel
- Phase 6: T050, T051 (tests) in parallel
- Phase 7: T056, T057, T058 (tests) in parallel

---

## Parallel Examples: US1 (Manage Projects)

```bash
# Run all US1 tests together (write first, confirm red):
Task: "Create project creation feature tests" → tests/Feature/Projects/CreateProjectTest.php
Task: "Create project update feature tests"   → tests/Feature/Projects/UpdateProjectTest.php
Task: "Create project deletion feature tests" → tests/Feature/Projects/DeleteProjectTest.php

# Then run all US1 action classes together (after tests are red):
Task: "Create CreateProject Action" → app/Actions/Projects/CreateProject.php
Task: "Create UpdateProject Action" → app/Actions/Projects/UpdateProject.php
Task: "Create DeleteProject Action" → app/Actions/Projects/DeleteProject.php
```

---

## Implementation Strategy

### MVP First (US0 + US1 Only — Phases 1-4)

1. Complete Phase 1: Setup
2. Complete Phase 2: Foundational
3. Complete Phase 3: Auth (tests + verify Breeze)
4. Complete Phase 4: US1 Projects
5. **STOP AND VALIDATE**: Full project CRUD works, all tests green
6. User can log in, create/edit/delete projects — MVP delivered

### Incremental Delivery

1. Setup + Foundational + Auth → Base app running
2. + US1 Projects → **MVP** (projects list + CRUD)
3. + US2 Tickets → Users can track work items
4. + US3 Board → Kanban workflow active
5. + US4 Dashboard + Export → Full feature complete
6. + Polish → Production-ready

### Running Tests

```bash
# Full suite
./vendor/bin/pest

# Only Feature tests
./vendor/bin/pest tests/Feature

# Only Unit tests
./vendor/bin/pest tests/Unit

# Specific story
./vendor/bin/pest tests/Feature/Projects

# With coverage report
./vendor/bin/pest --coverage --min=80
```

---

## Notes

- **TDD is mandatory**: Write test → confirm failure → implement → confirm pass → refactor
- **Adversarial testing**: Every story must have tests for: invalid input, auth boundary, other-user access, edge values
- **[P] tasks** = different files, truly parallelizable
- **[Story] label** maps task to user story for traceability and independent delivery
- **Frontend skill**: All Livewire + Blade UI tasks MUST use `/frontend-design` skill for mobile-first, polished output
- **N+1 guard**: Board and dashboard use `withCount` + eager loading — add `assertQueryCount` assertions in feature tests to lock this in
- Commit after each phase checkpoint (all tests green)
