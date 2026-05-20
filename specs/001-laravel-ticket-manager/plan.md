# Implementation Plan: EasyTicket — Ticket & Project Management System

**Branch**: `main` | **Date**: 2026-05-19 | **Spec**: [spec.md](./spec.md)

**Input**: Feature specification from `specs/001-laravel-ticket-manager/spec.md`

## Summary

Build EasyTicket: a personal ticket and project management web application (Jira/Trello-style)
using Laravel 13 with Livewire Volt, SQLite, and Docker Compose. The system provides a kanban
board per project, touch-aware drag-and-drop status management, a cross-project dashboard with
attention indicators, and a full JSON data export. Authentication uses the Livewire starter kit.
All features are developed TDD-first using Pest with Feature + Unit test layers.

## Technical Context

**Language/Version**: PHP 8.3 (required for Laravel 13)

**Primary Dependencies**:
- Laravel 13 (full-stack framework)
- Livewire 3 + Volt (server-side reactive UI, starter kit auth)
- Pest 3 + pest-plugin-laravel (TDD test framework)
- SortableJS (touch-aware drag-and-drop for kanban board)
- Tailwind CSS 3 (via Vite, included in Livewire starter kit)
- Alpine.js (included with Livewire stack, for lightweight JS interactions)
- Docker Compose (local development runtime)

**Storage**: SQLite 3 (single-file DB, WAL mode enabled, foreign key enforcement on)

**Testing**: Pest 3 + pest-plugin-laravel; two layers:
- Feature tests: HTTP full-cycle against a dedicated SQLite test DB
- Unit tests: pure business logic (Actions, Models, Enums) in isolation

**Target Platform**: Linux Docker container (PHP-FPM + Nginx or Caddy), self-hosted

**Project Type**: Full-stack web application (server-side rendering via Livewire Volt)

**Performance Goals**:
- Board status update perceived within 500ms
- Dashboard loads all data in single Livewire mount (no secondary round trips)
- Docker environment ready in under 60 seconds

**Constraints**:
- Mobile-first layout, usable at 320px width, no horizontal scroll
- No N+1 queries (all lists/boards use eager loading)
- No micro-optimizations; use Laravel/PHP built-in tools only
- No custom authentication (Livewire starter kit only)
- No multi-user features; single-owner data model
- All business logic in Action classes (HTTP-context-free for future MCP compatibility)

**Scale/Scope**: Single user, tens of projects, up to 50 tickets per project

## Constitution Check

*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

| Principle | Status | Notes |
|-----------|--------|-------|
| I. User Experience First | ✅ Pass | Mobile-first, touch DnD, single-visit dashboard load, <500ms board update |
| II. Data Integrity & Auditability | ⚠️ Partial | `updated_at` timestamps on all entities; full per-transition audit log deferred to v2 (see Complexity Tracking). Data validated at service boundary (Form Requests). Cascade deletes protected by confirmation. |
| III. TDD (NON-NEGOTIABLE) | ✅ Pass | Pest mandatory, Feature + Unit tests, adversarial scenarios required before any implementation |
| IV. Security & Access Control | ✅ Pass | All routes behind `auth` middleware. User ownership checked on every resource mutation. No secrets in version control (`.env` in `.gitignore`). Rate limiting via Laravel throttle. |
| V. Simplicity & YAGNI | ✅ Pass | No custom auth, no repository pattern, no abstract interfaces for single implementations. Eloquent direct. Action classes only where MCP-readiness requires HTTP-context isolation. |

*Post-Phase 1 re-check*: All gates still pass. No new violations introduced by design decisions.

## Project Structure

### Documentation (this feature)

```text
specs/001-laravel-ticket-manager/
├── plan.md              # This file
├── research.md          # Phase 0 output
├── data-model.md        # Phase 1 output
├── quickstart.md        # Phase 1 output
├── contracts/
│   └── routes.md        # URL contracts and Livewire component map
└── tasks.md             # Phase 2 output (/speckit-tasks command)
```

### Source Code (repository root)

```text
app/
├── Actions/
│   ├── Projects/
│   │   ├── CreateProject.php
│   │   ├── UpdateProject.php
│   │   └── DeleteProject.php
│   ├── Tickets/
│   │   ├── CreateTicket.php
│   │   ├── UpdateTicket.php
│   │   ├── UpdateTicketStatus.php
│   │   └── DeleteTicket.php
│   └── Export/
│       └── ExportUserData.php
├── Enums/
│   ├── TicketStatus.php
│   └── TicketPriority.php
├── Http/
│   ├── Middleware/           (auth enforced globally via route groups)
│   └── Requests/
│       ├── StoreProjectRequest.php
│       ├── UpdateProjectRequest.php
│       ├── StoreTicketRequest.php
│       └── UpdateTicketRequest.php
├── Livewire/                 (Volt single-file components)
│   ├── Dashboard/
│   ├── Projects/
│   └── Tickets/
└── Models/
    ├── Project.php
    ├── Ticket.php
    └── User.php

database/
├── migrations/
│   ├── 0001_01_01_000000_create_users_table.php   (Laravel default)
│   ├── xxxx_create_projects_table.php
│   └── xxxx_create_tickets_table.php
└── seeders/
    └── DatabaseSeeder.php    (demo data for development only)

resources/
├── css/
│   └── app.css               (Tailwind entry)
├── js/
│   └── app.js                (Alpine + SortableJS init)
└── views/
    ├── components/
    │   └── layouts/
    │       └── app.blade.php
    └── livewire/
        ├── dashboard.blade.php
        ├── projects/
        │   ├── index.blade.php
        │   ├── show.blade.php      (kanban board)
        │   ├── create.blade.php
        │   └── edit.blade.php
        └── tickets/
            ├── show.blade.php
            └── create.blade.php

tests/
├── Feature/
│   ├── Auth/
│   │   └── AuthenticationTest.php
│   ├── Projects/
│   │   ├── CreateProjectTest.php
│   │   ├── UpdateProjectTest.php
│   │   └── DeleteProjectTest.php
│   ├── Tickets/
│   │   ├── CreateTicketTest.php
│   │   ├── UpdateTicketTest.php
│   │   ├── UpdateTicketStatusTest.php
│   │   └── DeleteTicketTest.php
│   ├── Dashboard/
│   │   ├── DashboardTest.php
│   │   └── ExportTest.php
│   └── Board/
│       └── KanbanBoardTest.php
└── Unit/
    ├── Actions/
    │   └── ExportUserDataTest.php
    └── Models/
        ├── TicketOverdueTest.php
        ├── TicketPrioritySortTest.php
        └── TicketStatusTransitionTest.php

docker/
└── nginx.conf                (or Caddy config)
Dockerfile
docker-compose.yml
docker-compose.override.yml.example
.env.example
```

**Structure Decision**: Single Laravel project at repository root. Livewire Volt handles all
UI components as single-file components. Action classes isolate business logic from HTTP context.
No separate frontend build project — Tailwind CSS via Vite within the Laravel project.

## Complexity Tracking

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|--------------------------------------|
| Principle II partial compliance: no full per-transition audit log | Constitution mandates immutable audit trail (actor, timestamp, prev state, new state). DR-005 defers activity log to v2. | In v1 with a single user, `updated_at` on Ticket provides timestamp + current state = sufficient traceability. A dedicated `ticket_status_changes` table would double write complexity on every status update and add a migration with no v1 consumer. Full audit log is v2 work. |
| Action classes (slight abstraction over thin controllers) | Required by FR-015 (MCP-readiness) — business logic must be callable without HTTP context | Without Actions, all logic lives in Livewire components, making future MCP tool wrappers impossible without refactoring every component |
