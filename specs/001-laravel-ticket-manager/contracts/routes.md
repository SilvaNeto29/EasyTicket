# Route & Component Contracts: EasyTicket

**Date**: 2026-05-19

All routes are protected by `auth` middleware unless marked `[public]`.
All state-mutating routes are protected by `verified` middleware (email verification, Breeze default).

---

## Route Map

| Method | URI | Livewire Component / Controller | Name | Auth |
|--------|-----|--------------------------------|------|------|
| GET | `/` | Redirect → `/dashboard` | `home` | required |
| GET | `/dashboard` | `livewire/dashboard.blade.php` | `dashboard` | required |
| GET | `/projects` | `livewire/projects/index.blade.php` | `projects.index` | required |
| GET | `/projects/create` | `livewire/projects/create.blade.php` | `projects.create` | required |
| GET | `/projects/{project}` | `livewire/projects/show.blade.php` | `projects.show` | required |
| GET | `/projects/{project}/edit` | `livewire/projects/edit.blade.php` | `projects.edit` | required |
| GET | `/tickets/create` | `livewire/tickets/create.blade.php` | `tickets.create` | required |
| GET | `/tickets/{ticket}` | `livewire/tickets/show.blade.php` | `tickets.show` | required |
| GET | `/export` | `ExportController@download` | `export.download` | required |
| [public] | `/login`, `/register`, etc. | Breeze Livewire defaults | — | none |

---

## Livewire Component Contracts

### `dashboard` component

**Properties**:
- `$projects` — collection of Project with `total_tickets`, `open_tickets`, `overdue_tickets` counts
- `$attentionTickets` — collection of Tickets that are overdue or Critical priority

**Actions**:
- `downloadExport()` — dispatches redirect to `route('export.download')`

**Mount**: Loads all data in `mount()`. No lazy loading on dashboard.

---

### `projects.index` component

**Properties**:
- `$projects` — collection of user's Projects with ticket count aggregates

**Actions**:
- `deleteProject(int $projectId)` — validates ownership, calls `DeleteProject` action
- `confirmDelete(int $projectId)` — opens confirmation modal

---

### `projects.create` component

**Properties** (form):
- `$name` (string, required)
- `$description` (string, nullable)
- `$color` (string, nullable, hex)

**Actions**:
- `save()` — validates via `StoreProjectRequest` rules, calls `CreateProject` action, redirects to board

---

### `projects.show` (Kanban Board) component

**Properties**:
- `$project` — Project model (with tickets eager-loaded)
- `$columns` — tickets grouped by status, each sorted by priority then due_date
- `$filterPriority` (string|null) — active priority filter
- `$filterOverdue` (bool) — show only overdue tickets

**Actions**:
- `updateTicketStatus(int $ticketId, string $newStatus)` — called by SortableJS `onEnd` dispatch; validates ownership; calls `UpdateTicketStatus` action
- `applyFilter()` — re-loads tickets with current filter state

**Listeners**:
- `#[On('ticket-status-changed')]` → `updateTicketStatus()`

---

### `projects.edit` component

**Properties** (same as `projects.create`)

**Actions**:
- `save()` — validates, calls `UpdateProject` action, redirects back to board

---

### `tickets.create` component

**Properties**:
- `$projectId` (int, required — from query param)
- `$title`, `$description`, `$priority`, `$status`, `$due_date`

**Actions**:
- `save()` — validates via `StoreTicketRequest` rules, calls `CreateTicket` action, redirects to ticket detail

---

### `tickets.show` component

**Properties**:
- `$ticket` — Ticket model (with project)
- `$editing` (bool) — inline edit toggle

**Actions**:
- `updateStatus(string $status)` — calls `UpdateTicketStatus`
- `save()` — calls `UpdateTicket`
- `deleteTicket()` — calls `DeleteTicket`, redirects to project board

---

## Action Class Contracts

Each Action takes only domain objects (no Request, no auth facade — caller passes the User).

### `CreateProject`
```
Input:  User $user, string $name, ?string $description, ?string $color
Output: Project
Throws: ValidationException if name empty or invalid color
```

### `UpdateProject`
```
Input:  Project $project, string $name, ?string $description, ?string $color
Output: Project
Throws: ValidationException
```

### `DeleteProject`
```
Input:  Project $project
Output: void
Side-effects: Cascades to all tickets (DB-level FK CASCADE)
```

### `CreateTicket`
```
Input:  Project $project, User $user, string $title, ?string $description,
        TicketPriority $priority, TicketStatus $status, ?Carbon $due_date
Output: Ticket
Throws: ValidationException
```

### `UpdateTicket`
```
Input:  Ticket $ticket, array $data (title, description, priority, due_date)
Output: Ticket
Throws: ValidationException
```

### `UpdateTicketStatus`
```
Input:  Ticket $ticket, TicketStatus $newStatus
Output: Ticket
Throws: InvalidArgumentException if $newStatus is not a valid TicketStatus case
Side-effects: touches $ticket->updated_at (status change timestamp)
```

### `DeleteTicket`
```
Input:  Ticket $ticket
Output: void
```

### `ExportUserData`
```
Input:  User $user
Output: array (nested PHP array — JSON-serializable)
Guarantees: No N+1 (single projects query with tickets eager-loaded)
```

---

## Request Validation Contracts

### `StoreProjectRequest` / `UpdateProjectRequest`

| Field | Rule |
|-------|------|
| name | required, string, min:3, max:255 |
| description | nullable, string, max:5000 |
| color | nullable, string, regex:/^#[0-9A-Fa-f]{6}$/ |

### `StoreTicketRequest`

| Field | Rule |
|-------|------|
| project_id | required, exists:projects,id |
| title | required, string, min:3, max:255 |
| description | nullable, string |
| status | required, in:backlog,todo,in_progress,in_review,done,cancelled |
| priority | required, in:low,medium,high,critical |
| due_date | nullable, date, date_format:Y-m-d |

### `UpdateTicketRequest`

| Field | Rule |
|-------|------|
| title | required, string, min:3, max:255 |
| description | nullable, string |
| priority | required, in:low,medium,high,critical |
| due_date | nullable, date, date_format:Y-m-d |

---

## Authorization Rules

All checks performed in Livewire component before calling Action (or via Policy):

| Resource | Check |
|----------|-------|
| Project | `$project->user_id === auth()->id()` |
| Ticket | `$ticket->project->user_id === auth()->id()` |
| Export | Authenticated — always exports only the current user's data |

Authorization failures return 403 (Abort) or redirect with error flash.
