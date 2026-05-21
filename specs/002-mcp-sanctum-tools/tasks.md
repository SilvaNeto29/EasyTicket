---
description: "Task list for EasyTicket MCP Server with Sanctum Authentication"
---

# Tasks: EasyTicket — MCP Server with Sanctum Authentication

**Input**: Design documents from `specs/002-mcp-sanctum-tools/`

**Prerequisites**: plan.md ✅ | spec.md ✅ | research.md ✅ | data-model.md ✅ | contracts/mcp-tools.md ✅

**Tests**: MANDATORY (TDD). Every phase includes Pest tests written and FAILING before implementation. Covers auth, user isolation, validation errors, and happy paths.

**Organization**: Tasks grouped by user story; each story is an independently testable increment.

## Format: `[ID] [P?] [Story?] Description`

- **[P]**: Can run in parallel (different files, no dependencies on incomplete tasks)
- **[Story]**: User story label (US1=Token Management, US2=Project Tools, US3=Ticket Tools, US4=Export Tool, US5=Security)
- File paths are relative to repository root

---

## Phase 1: Setup

**Purpose**: Install and configure `laravel/mcp` and enable Sanctum token API. Must complete before any MCP tool work.

- [ ] T001 Verify `laravel/sanctum` is installed and `personal_access_tokens` migration exists; if not, run `php artisan vendor:publish --tag=sanctum-migrations` and `php artisan migrate`
- [ ] T002 Add `HasApiTokens` trait from `Laravel\Sanctum\HasApiTokens` to `app/Models/User.php` (import + use statement)
- [ ] T003 Install `laravel/mcp` via `composer require laravel/mcp`; verify package is in `composer.json` and autoloaded
- [ ] T004 [P] Create directory `app/Mcp/Tools/` for tool class files
- [ ] T005 [P] Create directory `tests/Feature/Mcp/` for MCP test files

**Checkpoint**: `php artisan tinker --execute="auth()->user()"` works; `vendor/laravel/mcp` directory exists.

---

## Phase 2: Foundational

**Purpose**: Configure the MCP endpoint with Sanctum auth. All tool tests depend on this being correct.

- [ ] T006 Publish and review `laravel/mcp` config (if publishable): `php artisan vendor:publish --tag=mcp-config`; confirm `/mcp` route is registered and uses `auth:sanctum` middleware
- [ ] T007 Write tests in `tests/Feature/Mcp/McpAuthTest.php` BEFORE any tool implementation:
  - Missing Authorization header → 401
  - Invalid token → 401
  - Revoked token → 401 (create token, revoke it, call `/mcp`)
  - Valid token → 200 (or MCP protocol response, not 401)
  - `tools/list` with valid token returns non-empty tools array
- [ ] T008 Configure MCP route with `auth:sanctum` middleware in `routes/web.php` or via `laravel/mcp` config; register Tool classes in service provider or config; run T007 tests to confirm red→green

**Checkpoint**: `McpAuthTest` fully green. Invalid tokens are rejected at the HTTP level before any tool logic runs.

---

## Phase 3: US1 — Token Management UI

**Story goal**: User can generate, view, and revoke personal access tokens from the Profile page.

**Independent test criteria**: Token CRUD works without any MCP tool being callable.

- [ ] T009 [US1] Write tests in `tests/Feature/Profile/ApiTokensTest.php`:
  - Authenticated user can view the tokens section on profile page
  - User can generate a token with a valid name → response contains plaintext token
  - Generated token appears in the token list (name + date shown, not value)
  - Empty name is rejected with validation error
  - User can revoke a token → token row removed from database
  - Revoked token returns 401 on a subsequent auth:sanctum protected call
  - Token belongs to the creating user only (another user's token list is empty)

- [ ] T010 [US1] Create Livewire Volt component `resources/views/livewire/profile/api-tokens.blade.php`:
  - `#[Computed] tokens()` — returns `Auth::user()->tokens()->latest()->get()`
  - `createToken(string $name)` — validates name not empty, calls `$user->createToken($name)`, stores plaintext in `$this->newTokenValue` for one-time display
  - `revokeToken(int $tokenId)` — deletes token owned by current user only (abort 403 if not owner)
  - `$newTokenValue` public property (nullable string) — shown once after generation, then cleared

- [ ] T011 [US1] Add the `api-tokens` Volt component to the existing profile page view `resources/views/livewire/profile/edit.blade.php` (or the profile layout that renders all profile sections); position below existing sections

- [ ] T012 [US1] UI: token list shows name + created date; new token value displayed in a one-time copyable box with a "Done" button that clears `$newTokenValue`; each token row has a "Revoke" button with confirmation; mobile-first layout

**Checkpoint**: `ApiTokensTest` fully green. User can generate and revoke tokens in the browser.

---

## Phase 4: US2 — Project Tools

**Story goal**: MCP client can list, create, update, and delete the authenticated user's projects.

**Independent test criteria**: All 4 project tools return correct data for the token owner; cross-user attempts return not-found.

- [ ] T013 [US2] [P] Write tests in `tests/Feature/Mcp/McpProjectToolsTest.php`:
  - `list_projects` → returns only the authenticated user's projects (not other users')
  - `list_projects` → empty array when user has no projects
  - `create_project` with valid name → project created, returned in subsequent `list_projects`
  - `create_project` with empty name → isError response with validation message
  - `create_project` with name < 3 chars → isError response
  - `update_project` valid fields → project updated, changes persisted
  - `update_project` with project_id belonging to another user → not-found error
  - `delete_project` → project and all its tickets removed from DB
  - `delete_project` with another user's project_id → not-found error

- [ ] T014 [US2] Create `app/Mcp/Tools/ProjectTools.php` with methods decorated for `laravel/mcp`:
  - `list_projects()` — `Project::where('user_id', Auth::id())->withCount([...])->get()`
  - `create_project(string $name, ?string $description, ?string $color)` — calls `app(CreateProject::class)->handle(Auth::user(), [...])`; catches `ValidationException`, returns isError
  - `update_project(int $project_id, ...)` — scopes to `user_id`, calls `app(UpdateProject::class)->handle(...)`; not-found if missing
  - `delete_project(int $project_id)` — scopes to `user_id`, calls `app(DeleteProject::class)->handle(...)`; not-found if missing
  - Register class in MCP config/service provider

**Checkpoint**: `McpProjectToolsTest` fully green.

---

## Phase 5: US3 — Ticket Tools

**Story goal**: MCP client can list, create, update, change status, and delete tickets within a project.

**Independent test criteria**: All 5 ticket tools respect user ownership; invalid status values return descriptive errors.

- [ ] T015 [US3] [P] Write tests in `tests/Feature/Mcp/McpTicketToolsTest.php`:
  - `list_tickets` with own project_id → returns all tickets for that project
  - `list_tickets` with another user's project_id → not-found error
  - `list_tickets` on project with zero tickets → empty array (not error)
  - `create_ticket` with valid data → ticket created with correct defaults (priority=medium, status=backlog)
  - `create_ticket` missing title → isError validation message
  - `create_ticket` with invalid priority value → isError
  - `update_ticket` partial update → only specified fields change, others unchanged
  - `update_ticket` with another user's ticket_id → not-found error
  - `update_ticket_status` with valid status → status updated, persisted
  - `update_ticket_status` with invalid status value → isError with descriptive message
  - `delete_ticket` → ticket removed from DB
  - `delete_ticket` with another user's ticket_id → not-found error

- [ ] T016 [US3] Create `app/Mcp/Tools/TicketTools.php` with methods decorated for `laravel/mcp`:
  - `list_tickets(int $project_id)` — verify project belongs to `Auth::user()`, then `$project->tickets()->get()`
  - `create_ticket(int $project_id, string $title, ...)` — verify project ownership, call `app(CreateTicket::class)->handle(...)`
  - `update_ticket(int $ticket_id, ...)` — scope `Ticket::where('user_id', Auth::id())`, call `app(UpdateTicket::class)->handle(...)`
  - `update_ticket_status(int $ticket_id, string $new_status)` — validate status is valid `TicketStatus` case, call `app(UpdateTicketStatus::class)->handle(...)`
  - `delete_ticket(int $ticket_id)` — scope to user, call `app(DeleteTicket::class)->handle(...)`
  - Register class in MCP config/service provider

**Checkpoint**: `McpTicketToolsTest` fully green.

---

## Phase 6: US4 — Export Tool

**Story goal**: MCP client can retrieve the full dataset for the authenticated user.

**Independent test criteria**: `export_data` returns correct structure; never includes another user's data.

- [ ] T017 [US4] [P] Write tests in `tests/Feature/Mcp/McpExportToolTest.php`:
  - `export_data` → returns JSON string parseable to array of projects with tickets
  - `export_data` with no projects → returns empty array `[]`
  - `export_data` → does not include other users' projects or tickets
  - Returned structure matches the browser export format (same fields as `ExportUserData` action)

- [ ] T018 [US4] Create `app/Mcp/Tools/ExportTools.php`:
  - `export_data()` — calls `app(ExportUserData::class)->handle(Auth::user())`, JSON-encodes result, returns as text content
  - Register class in MCP config/service provider

**Checkpoint**: `McpExportToolTest` fully green.

---

## Phase 7: US5 — Security & Cross-User Isolation (cross-cutting)

**Story goal**: No data leakage between users under any combination of tool calls.

**Independent test criteria**: Adversarial tests with two users and swapped tokens all return errors, never the other user's data.

- [ ] T019 [US5] Write tests in `tests/Feature/Mcp/McpUserIsolationTest.php`:
  - User A token + User B's project_id in `list_tickets` → not-found (never returns B's tickets)
  - User A token + User B's project_id in `update_project` → not-found
  - User A token + User B's project_id in `delete_project` → not-found (B's project still exists after call)
  - User A token + User B's ticket_id in `update_ticket` → not-found
  - User A token + User B's ticket_id in `delete_ticket` → not-found (B's ticket still exists)
  - `list_projects` with User A's token never includes User B's projects in response body
  - `export_data` with User A's token never includes User B's data

- [ ] T020 [US5] Verify all tool methods use `->where('user_id', Auth::id())` or equivalent ownership scope before any DB write or read; fix any gap found during T019

**Checkpoint**: `McpUserIsolationTest` fully green. No tool returns another user's data under any input.

---

## Phase 8: Polish & Integration

- [ ] T021 Run full Pest suite `./vendor/bin/pest --no-coverage`; fix any regressions introduced by `HasApiTokens` or Sanctum config changes
- [ ] T022 [P] Rebuild Docker image `docker compose up -d --build`; verify `/mcp` endpoint reachable at `http://localhost:8080/mcp` and returns 401 for unauthenticated requests
- [ ] T023 [P] Update `specs/002-mcp-sanctum-tools/quickstart.md` with Claude Desktop configuration example (server URL, auth header setup, token generation steps)

---

## Dependencies

```
Phase 1 (Setup)
  └─► Phase 2 (Foundational / MCP auth)
        └─► Phase 3 (US1 Token UI)    ← can start after Phase 2
        └─► Phase 4 (US2 Projects)    ← can start after Phase 2
        └─► Phase 5 (US3 Tickets)     ← can start after Phase 2
        └─► Phase 6 (US4 Export)      ← can start after Phase 2
        └─► Phase 7 (US5 Isolation)   ← requires Phases 3-6 complete
              └─► Phase 8 (Polish)
```

Phases 3–6 are independent of each other and can be implemented in parallel.

---

## Parallel Execution Examples

**After Phase 2 completes**, the following can run simultaneously:
- T009–T012 (Token UI)
- T013–T014 (Project Tools)
- T015–T016 (Ticket Tools)
- T017–T018 (Export Tool)

**MVP scope**: Phase 1 + Phase 2 + Phase 4 (project tools) — demonstrates end-to-end MCP connectivity with real data.

---

## Implementation Strategy

1. **Setup first** (T001–T005): dependencies, directories
2. **Auth gate** (T006–T008): nothing works without this
3. **Token UI** (T009–T012): users can't connect MCP clients without tokens
4. **Tools in parallel** (T013–T020): each tool group is independent
5. **Isolation audit** (T019–T020): adversarial pass across all tools
6. **Polish** (T021–T023): Docker rebuild, regression check, docs
