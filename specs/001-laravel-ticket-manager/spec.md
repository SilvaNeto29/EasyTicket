# Feature Specification: EasyTicket — Ticket & Project Management System

**Feature Branch**: `001-laravel-ticket-manager`

**Created**: 2026-05-19

**Status**: Draft

**Input**: User description: "Laravel 13 project in the current directory, SQLite, Livewire starter kit auth,
MCP-ready (post-launch), ticket and project management like Jira/Trello, beautiful UI, secure, single
primary user, project-based division (no teams), email notifications deferred, framework-level
optimizations, Eloquent ORM with N+1 awareness, mobile-first, Dockerized."

---

## Clarifications

### Session 2026-05-19

- Q: How should status changes work on mobile devices (drag-and-drop vs tap)? → A: Touch-aware drag-and-drop using SortableJS touch events — same interaction as desktop on all screen sizes.

### Session 2026-05-19 (testing mandate)

- Directive: Tests are MANDATORY. TDD cycle enforced: tests written and failing BEFORE implementation. Tests MUST NOT cover only the happy path — edge cases, invalid inputs, authorization failures, concurrent access, and fault conditions are first-class test targets. The goal is to break the application in tests before a bug can reach production.
- Q: Which test framework? → A: Pest — function-based, expressive syntax (`it('rejects unauthenticated access', ...)`) with `pest-plugin-laravel` for full Laravel integration.
- Q: Which test types are in scope? → A: Feature tests + Unit tests. Feature tests cover HTTP flows, authentication, authorization, validation, and DB state against a real SQLite test DB. Unit tests cover pure business logic: priority sorting, overdue detection, status transition rules, export formatting.
- Q: Can tickets be manually reordered within a column? → A: No manual reordering; tickets are auto-sorted by priority (Critical → High → Medium → Low), then by due date ascending within each column.
- Q: What language should the UI be in? → A: English — all labels, messages, error text, and UI copy in English.
- Q: What level of login brute-force protection is required? → A: Framework defaults — Laravel's built-in rate limiting (5 attempts, 60-second lockout) already provided by the Livewire starter kit; no additional hardening needed.
- Q: What backup/data-recovery expectation should the system meet? → A: Simple in-app export — a button on the dashboard downloads a full JSON snapshot of all projects and tickets.

---

## User Scenarios & Testing *(mandatory)*

### User Story 1 — Manage Projects (Priority: P1)

As the system owner, I want to create and organize projects so that I can group related tickets
together and track work across different areas of my life or business.

**Why this priority**: Projects are the top-level container for all work. Without projects, no
ticket management is possible. This is the entry point to the entire system.

**Independent Test**: A user can open the app, create a project with a name and description,
see it listed on the dashboard, edit it, and delete it — without needing any ticket to exist.

**Acceptance Scenarios**:

1. **Given** the user is logged in, **When** they navigate to the Projects page, **Then** they
   see a list of all their projects with name, description, and ticket count summary.
2. **Given** the user is on the Projects page, **When** they create a new project with a name,
   description, and optional color, **Then** the project appears in the list immediately.
3. **Given** a project exists, **When** the user edits its name or description, **Then** changes
   are saved and reflected throughout the system.
4. **Given** a project exists with no tickets, **When** the user deletes it, **Then** it is
   removed from the list permanently.
5. **Given** a project has tickets, **When** the user attempts to delete it, **Then** the system
   warns about cascading deletion and requires explicit confirmation.

---

### User Story 2 — Create and Track Tickets (Priority: P2)

As the system owner, I want to create tickets within a project so that I can capture, describe,
and prioritize individual pieces of work.

**Why this priority**: Tickets are the core unit of work tracking. All other features (status
management, filtering, dashboard) depend on tickets existing.

**Independent Test**: Within a project, a user can create a ticket with a title, description,
priority, and due date, view its detail page, edit any field, and delete it — independently of
ticket board/column views.

**Acceptance Scenarios**:

1. **Given** the user is inside a project, **When** they create a ticket with a title,
   description, priority (Low / Medium / High / Critical), and optional due date, **Then** the
   ticket appears in the project board under the correct status column.
2. **Given** a ticket exists, **When** the user opens its detail view, **Then** they see all
   fields, the full description, current status, priority, and creation/update timestamps.
3. **Given** a ticket exists, **When** the user edits the title, description, priority, or due
   date, **Then** changes are persisted and immediately visible.
4. **Given** a ticket is overdue (due date has passed and status is not Done or Cancelled),
   **When** the user views the project board or ticket list, **Then** the ticket is visually
   flagged as overdue.
5. **Given** a ticket exists, **When** the user deletes it, **Then** it is permanently removed
   after a confirmation prompt.

---

### User Story 3 — Manage Ticket Workflow (Priority: P3)

As the system owner, I want to move tickets across status columns so that I can visually track
the progress of each piece of work from start to completion.

**Why this priority**: Status progression turns a static list into a live workflow tool. It
provides the visual, interactive feel the user expects from a Jira/Trello-style system.

**Independent Test**: A user can drag a ticket card from one status column to another (or use a
status dropdown on the detail page) and see the change reflected immediately in the board view.

**Acceptance Scenarios**:

1. **Given** a project board is open, **When** the user drags a ticket card from "Backlog" to
   "In Progress", **Then** the board updates immediately and the status is persisted.
2. **Given** the ticket detail page is open, **When** the user changes the status via a
   dropdown, **Then** the board view reflects the new status without a full page reload.
3. **Given** a ticket is in "Done" status, **When** the user views the project board, **Then**
   done tickets are visually distinct (muted or in a collapsed section) but still accessible.
4. **Given** tickets exist across all status columns, **When** the user filters by priority or
   due date, **Then** only matching tickets are visible in each column, preserving column
   structure.
5. **Given** a column has multiple tickets, **When** the board renders, **Then** tickets are
   ordered by priority (Critical → High → Medium → Low) and, within the same priority, by
   due date ascending (earliest first; tickets without a due date appear last).

**Default status columns**: Backlog → To Do → In Progress → In Review → Done → Cancelled.
Statuses are fixed for v1 (no custom columns).

---

### User Story 4 — Dashboard Overview (Priority: P4)

As the system owner, I want a home dashboard that shows me the state of all my projects and
highlights urgent or overdue tickets so that I can quickly understand what needs my attention.

**Why this priority**: Once projects and tickets exist, the user needs an at-a-glance view that
surfaces the most important information without navigating each project individually.

**Independent Test**: After creating two projects with tickets of different statuses and
priorities, the dashboard shows each project's ticket summary and highlights any overdue or
critical tickets — loading all data in a single visit.

**Acceptance Scenarios**:

1. **Given** projects exist, **When** the user opens the dashboard, **Then** they see a summary
   card per project showing: project name, total ticket count, open ticket count, and overdue
   count.
2. **Given** tickets are overdue or have Critical priority, **When** the user views the
   dashboard, **Then** those tickets appear in an "Attention Required" section at the top.
3. **Given** no projects exist, **When** the user opens the dashboard, **Then** they see a
   welcoming empty state with a clear call-to-action to create their first project.
4. **Given** the user clicks the "Export Data" action on the dashboard, **When** the export
   completes, **Then** a JSON file is downloaded containing all projects and their tickets with
   every field included.

---

### User Story 5 — Authentication (Priority: P0 — Foundation)

As a user, I want to securely log in to and out of the system so that my data is protected
from unauthorized access.

**Why this priority**: Authentication is a prerequisite for every other story.

**Independent Test**: A fresh browser session cannot access any page. After logging in, all
pages are accessible. After logging out, pages redirect to login again.

**Acceptance Scenarios**:

1. **Given** an unauthenticated visitor, **When** they access any protected URL, **Then** they
   are redirected to the login page.
2. **Given** the user enters valid credentials, **When** they submit the login form, **Then**
   they are redirected to the dashboard.
3. **Given** the user enters invalid credentials, **When** they submit the login form, **Then**
   an appropriate error message is displayed and no session is created.
4. **Given** the user is logged in, **When** they log out, **Then** the session is destroyed
   and they are redirected to the login page.
5. **Given** the user has forgotten their password, **When** they use the password reset flow,
   **Then** they can set a new password and log in successfully.
6. **Given** the user submits 5 consecutive failed login attempts, **When** they try again
   within 60 seconds, **Then** the form is temporarily locked and an appropriate message is
   shown (enforced by Laravel's built-in throttle middleware).

---

### Edge Cases

- What happens when a user tries to access a project URL that has been deleted? System returns
  a 404 page with a clear message and a link back to the projects list.
- What happens when the due date is set in the past at creation time? The system accepts it
  (back-dating is valid) and flags the ticket as overdue immediately.
- What happens if a project has no tickets? The board view shows empty columns with a
  call-to-action to create the first ticket.
- What happens on a very small screen (320px width)? All interactive elements remain accessible
  with touch targets of at least 44×44px; board columns stack vertically on mobile.
- What happens when the user session expires mid-navigation? The user is redirected to login
  with their intended destination preserved for redirect after re-authentication.

---

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST allow the authenticated user to create, view, edit, and delete projects.
- **FR-002**: Each project MUST display a ticket count summary (total, open, overdue) aggregated
  without loading all individual ticket records on the projects list page.
- **FR-003**: System MUST allow the user to create tickets within a project with: title,
  description, priority (Low / Medium / High / Critical), status, and optional due date.
- **FR-004**: System MUST enforce authentication on all routes; unauthenticated requests MUST
  be redirected to login.
- **FR-005**: System MUST support the fixed ticket status workflow: Backlog, To Do, In Progress,
  In Review, Done, Cancelled.
- **FR-006**: System MUST allow status changes via touch-aware drag-and-drop (SortableJS with
  touch event support) on the project board view, functioning identically on desktop and mobile.
- **FR-007**: System MUST allow status changes via a dropdown selector on the ticket detail page.
- **FR-008**: System MUST allow filtering tickets by priority and/or due date within a project.
- **FR-009**: System MUST visually flag tickets whose due date has passed and whose status is
  not Done or Cancelled.
- **FR-010**: System MUST present a dashboard home page summarizing all projects and surfacing
  attention-required tickets (overdue or Critical priority).
- **FR-011**: System MUST be fully usable on mobile screens 320px and above with a mobile-first
  layout (no horizontal scrolling).
- **FR-012**: System MUST be launchable via a single `docker compose up` command for local
  development.
- **FR-013**: System MUST use eager loading on all list and board views to prevent N+1 queries.
- **FR-014**: Deleting a project MUST cascade-delete all its tickets; user must confirm before
  deletion proceeds.
- **FR-015**: System architecture MUST isolate business logic in a layer accessible without HTTP
  context, enabling future MCP tool wrappers to call it directly.
- **FR-016**: System MUST provide a data export action on the dashboard that downloads a complete
  JSON snapshot of all the user's projects and their tickets (all fields included), suitable for
  backup and restore purposes.
- **FR-017**: Every feature MUST have automated tests written BEFORE implementation (TDD) using
  Pest with pest-plugin-laravel. Two test layers are required:
    - **Feature tests**: Full HTTP request lifecycle against a real SQLite test DB — covering
      authentication, authorization, request validation, response structure, and DB state changes.
    - **Unit tests**: Pure business logic in isolation — priority sort order, overdue detection
      logic, status transition rules, JSON export structure.
  Tests that only verify expected success flows are insufficient.
- **FR-018**: Test scenarios MUST include adversarial cases: submitting malformed or missing data,
  accessing another user's resources, triggering cascade deletes, hitting boundary values (empty
  strings, null optional fields, maximum field lengths), and verifying that invalid status
  transitions are rejected with the correct error response.

### Deferred Requirements (not in v1)

- **DR-001**: Email notifications on ticket creation, status change, or due-date proximity.
- **DR-002**: MCP server integration.
- **DR-003**: Multi-user collaboration or shared project access.
- **DR-004**: File attachments on tickets.
- **DR-005**: Ticket comments and activity history log.
- **DR-006**: Custom status columns or configurable workflows.

### Key Entities

- **Project**: Represents a work area or initiative. Has a name, optional description, color
  label, and belongs to the authenticated user. Aggregates ticket counts for display.
- **Ticket**: The atomic unit of work. Belongs to a project. Has a title, description, priority,
  status, optional due date, and timestamps. Within each status column, tickets are automatically
  sorted by priority (Critical first) then by due date ascending. No manual position field needed.
- **User**: The authenticated owner of projects and tickets. In v1, a single active user;
  data model supports future expansion to multiple users without schema migration.

---

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: A user can create their first project and add their first ticket in under 2 minutes
  from first login.
- **SC-002**: All pages are fully usable on a 375px-wide mobile screen with no horizontal
  scrolling and all touch targets at minimum 44×44px.
- **SC-003**: The dashboard loads all project summaries and attention items in a single page
  visit without secondary data-fetching round trips visible to the user.
- **SC-004**: The entire local development environment starts and is ready for use in under
  60 seconds after running the launch command.
- **SC-005**: Moving a ticket between status columns on the board is reflected on screen within
  500ms (perceived by the user).
- **SC-006**: No N+1 query patterns exist on any page in development mode (verifiable via
  query logging enabled by default in dev).
- **SC-007**: The system correctly displays up to 50 tickets per project without layout breakage
  or perceptible slowdown.
- **SC-008**: The JSON data export completes and triggers a file download in under 5 seconds
  regardless of the number of projects or tickets stored.
- **SC-009**: Every feature has a passing test suite before its implementation is merged. No
  feature is considered complete without tests that cover at least: one happy path, two invalid
  input scenarios, one authorization/boundary check, and one edge case.
- **SC-010**: The test suite catches at least one real bug per feature area before the
  implementation is considered production-ready (verified during development cycle).

---

## Assumptions

- **Technology stack**: Laravel 13, Livewire starter kit (auth scaffolding), SQLite, Docker
  Compose. These are fixed constraints chosen by the product owner.
- **Single primary user**: The initial deployment serves one user. All entities carry a user
  ownership reference so multi-user support can be added later without schema migration.
- **No team-based division**: Access control in v1 is binary (authenticated = full access to
  own data). Team roles and permissions are out of scope.
- **Email infrastructure deferred**: Email sending will not be implemented in v1. The application
  will be configured with a null or log mail driver by default. Email-triggering events will be
  stubbed with log entries.
- **Fixed status columns**: Ticket workflow uses six fixed statuses. Custom columns are out
  of scope for v1.
- **No file attachments or comments**: Ticket detail view is text/field-only in v1.
- **Testing is mandatory and TDD-enforced**: Tests are not optional. Every feature follows the
  Red → Green → Refactor cycle. Framework: Pest + pest-plugin-laravel. Two layers: Feature tests
  (full HTTP + real SQLite test DB) and Unit tests (pure business logic). Tests deliberately
  target edge cases, invalid inputs, authorization failures, and fault conditions — not only
  success flows. No feature is complete without adversarial test coverage.
- **Performance optimizations**: Will use Laravel and PHP built-in tools (Eloquent eager loading,
  framework cache). No micro-optimizations or custom low-level performance code.
- **Drag-and-drop**: Board drag-and-drop will use SortableJS with its touch event plugin enabled,
  providing identical interaction on desktop (mouse) and mobile (touch). Complex animation is not
  required.
- **MCP readiness**: Service/action classes will not depend on HTTP request context so they can
  be called directly by future MCP tool wrappers without refactoring.
- **UI language**: All interface text, labels, messages, and error copy are in English. No
  localization or translation layer is required in v1.
- **Deployment target**: Docker Compose for local development and self-hosted deployment.
  No cloud-provider-specific configuration assumed in v1.
