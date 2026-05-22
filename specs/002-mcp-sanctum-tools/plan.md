# Implementation Plan: MCP Server with Sanctum Authentication

**Branch**: `002-mcp-sanctum-tools` | **Date**: 2026-05-21 | **Spec**: [spec.md](spec.md)

**Input**: Feature specification from `specs/002-mcp-sanctum-tools/spec.md`

---

## Summary

Add an MCP (Model Context Protocol) server to EasyTicket that exposes the existing Action classes as 10 MCP Tools, authenticated via Laravel Sanctum personal access tokens. Users generate tokens in the Profile page; MCP clients (e.g. Claude Desktop) connect using `Authorization: Bearer <token>` and operate exclusively on the token owner's data.

**Package**: `laravel/mcp` v0.7.1 (official, first-party, Laravel 12 compatible).
**Transport**: Streamable HTTP, single `/mcp` endpoint.
**Auth**: `auth:sanctum` middleware with Bearer token.

---

## Technical Context

**Language/Version**: PHP 8.3, Laravel 12.60.1

**Primary Dependencies**:
- `laravel/mcp` ^0.7 — official MCP server package
- `laravel/sanctum` (already installed via Breeze) — personal access tokens

**Storage**: SQLite (existing). New table: `personal_access_tokens` (Sanctum migration).

**Testing**: Pest 3 + RefreshDatabase. TDD — tests written and failing before implementation.

**Target Platform**: Docker container (Alpine Linux), `php:8.3-fpm-alpine`

---

## Constitution Check

| Principle | Status | Notes |
|---|---|---|
| I. User Experience First | ✅ Pass | Token UI in Profile (minimal friction). MCP removes the need to open a browser for AI assistant users. |
| II. Data Integrity & Auditability | ✅ Pass | `last_used_at` updated on each call. No silent data mutations — all writes go through existing validated Actions. |
| III. TDD (NON-NEGOTIABLE) | ✅ Pass | All tests written before implementation. Covers auth, isolation, validation, happy path. |
| IV. Security & Access Control | ✅ Pass | `auth:sanctum` middleware gates the entire `/mcp` endpoint. Each tool re-validates ownership via `user_id`. Revoked tokens immediately rejected. |
| V. Simplicity & YAGNI | ✅ Pass | Using official package (no custom JSON-RPC). No token scopes/abilities in v1. No rate limiting in v1. Single endpoint. Tool classes delegate to existing Actions. |

---

## File Structure

```
app/
  Mcp/
    Tools/
      ProjectTools.php       # list_projects, create_project, update_project, delete_project
      TicketTools.php        # list_tickets, create_ticket, update_ticket, update_ticket_status, delete_ticket
      ExportTools.php        # export_data
  Models/
    User.php                 # add HasApiTokens trait
resources/views/livewire/
  profile/
    api-tokens.blade.php     # new Volt component: token list, generate, revoke
tests/
  Feature/
    Mcp/
      McpAuthTest.php        # invalid token, missing token, revoked token
      McpProjectToolsTest.php
      McpTicketToolsTest.php
      McpExportToolTest.php
      McpUserIsolationTest.php  # cross-user access attempts
  Feature/
    Profile/
      ApiTokensTest.php      # token generate, list, revoke
```

---

## Implementation Phases

### Phase 0: Sanctum Token Infrastructure

1. Verify `personal_access_tokens` table migration exists; publish if not.
2. Add `HasApiTokens` trait to `app/Models/User.php`.
3. Verify `sanctum.php` config is published (or defaults are sufficient).

### Phase 1: Token Management UI (TDD)

**Tests first** — `tests/Feature/Profile/ApiTokensTest.php`:
- User can generate a token with a name → token value shown once
- User can list their active tokens (name + date, not value)
- User can revoke a token → token no longer authenticates
- Empty name rejected
- Revoked token returns 401 on next MCP call

**Implementation**:
- New Livewire Volt component `resources/views/livewire/profile/api-tokens.blade.php`
- Add to the existing profile page view
- Methods: `createToken(string $name)`, `revokeToken(int $tokenId)`, computed `tokens()`

### Phase 2: MCP Server Setup

1. `composer require laravel/mcp`
2. Publish `laravel/mcp` config if applicable.
3. Configure the `/mcp` route to use `auth:sanctum` middleware.
4. Register Tool classes in the MCP service provider / config.

### Phase 3: MCP Tool Classes (TDD)

**Tests first** for each tool group, covering:
- Happy path: correct input → correct output
- Auth boundary: valid token → user A's data only
- Isolation: user A's token → cannot access user B's resources
- Validation: missing required fields → descriptive error
- Not found: non-existent or foreign resource → not-found error

**Implementation**:
- `app/Mcp/Tools/ProjectTools.php` — 4 tools
- `app/Mcp/Tools/TicketTools.php` — 5 tools
- `app/Mcp/Tools/ExportTools.php` — 1 tool

Each tool method:
1. Resolves `Auth::user()` (already authenticated by middleware)
2. Validates input (or delegates to existing Action which validates)
3. Calls the existing Action class
4. Returns JSON string as `text` content

### Phase 4: Docker & Integration

- Rebuild Docker image to include `laravel/mcp` in `vendor/`
- Verify `/mcp` endpoint is reachable at `http://localhost:8080/mcp`
- Verify Claude Desktop can connect using a generated token

---

## Key Design Decisions

### Tool Method Structure

```php
// Example pattern — each tool follows this shape
#[Tool(description: 'Create a new project')]
public function create_project(string $name, ?string $description = null, ?string $color = null): string
{
    $user = Auth::user();
    try {
        $project = app(CreateProject::class)->handle($user, ['name' => $name, 'description' => $description, 'color' => $color]);
        return json_encode($project->toArray());
    } catch (ValidationException $e) {
        throw new \RuntimeException('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
    }
}
```

### Cross-User Security Pattern

Every tool that accepts a resource ID resolves it scoped to the authenticated user:
```php
$project = Project::where('id', $projectId)->where('user_id', $user->id)->firstOrFail();
```
`firstOrFail()` returns a 404 (caught and returned as MCP error) — the same response whether the resource doesn't exist or belongs to another user. No information leak.

### Sanctum Middleware

The `/mcp` route group:
```php
Route::middleware(['auth:sanctum'])->group(function () {
    // laravel/mcp registers its route here
});
```

`auth:sanctum` on a non-session request (no cookie) will check the Bearer token. Invalid → 401 before the MCP handler runs.

---

## Complexity Tracking

| Decision | Complexity Added | Justification |
|---|---|---|
| `laravel/mcp` package | Low — package handles protocol | Official first-party package; far simpler than manual JSON-RPC |
| Sanctum token API | Low — already installed | Enabling the token feature of an existing dependency |
| Tool classes in `app/Mcp/` | Low — thin wrappers | Zero business logic; all logic already in Actions |

No unjustified complexity. All complexity is directly required by the feature spec.

---

## Out of Scope (v1)

- Token scopes/abilities
- Token expiry dates
- Rate limiting per token
- MCP streaming / SSE notifications
- `list_resources`, `list_prompts` MCP capabilities
- Email notifications

---

## Dependencies on Existing Code

| Existing artifact | How used |
|---|---|
| `app/Actions/Projects/CreateProject.php` | Called by `create_project` tool |
| `app/Actions/Projects/UpdateProject.php` | Called by `update_project` tool |
| `app/Actions/Projects/DeleteProject.php` | Called by `delete_project` tool |
| `app/Actions/Tickets/CreateTicket.php` | Called by `create_ticket` tool |
| `app/Actions/Tickets/UpdateTicket.php` | Called by `update_ticket` tool |
| `app/Actions/Tickets/UpdateTicketStatus.php` | Called by `update_ticket_status` tool |
| `app/Actions/Tickets/DeleteTicket.php` | Called by `delete_ticket` tool |
| `app/Actions/Export/ExportUserData.php` | Called by `export_data` tool |
| `app/Models/Project.php` | Queried in `list_projects`, `list_tickets` |
| `app/Models/Ticket.php` | Queried in `list_tickets` |
