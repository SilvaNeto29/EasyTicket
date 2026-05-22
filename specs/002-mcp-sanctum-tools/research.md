# Research: MCP Server with Sanctum Authentication

**Feature**: `002-mcp-sanctum-tools`
**Date**: 2026-05-21

---

## Decision 1: MCP Package

**Decision**: Use `laravel/mcp` (official Laravel package, v0.7.1).

**Rationale**: First-party package maintained by Taylor Otwell under the `laravel` GitHub org. Requires PHP 8.2+ and Laravel 12.41.1+ — matches our stack exactly. 16.9M installs, MIT license, actively updated (last release 2026-05-20). No need to implement JSON-RPC 2.0 manually.

**Alternatives considered**:
- `php-mcp/laravel` (community, v4.0, 114k installs) — solid but third-party; prefer the official package when it covers the use case.
- Manual JSON-RPC 2.0 implementation — unnecessary given the official package.
- `logiscape/mcp-sdk-php` — framework-agnostic, more boilerplate for a Laravel project.

---

## Decision 2: MCP Transport

**Decision**: Streamable HTTP (single `/mcp` endpoint, POST + GET).

**Rationale**: This is the current MCP specification standard (protocol version `2025-06-18`). A single endpoint accepts JSON-RPC POSTs and may open SSE streams for notifications. `laravel/mcp` handles the transport layer — we only need to define Tools.

**The old HTTP+SSE transport** (separate `/mcp/sse` and `/mcp/messages` endpoints) is deprecated as of 2025-03-26 but may still be needed for older clients.

**Required lifecycle methods** (handled by `laravel/mcp`):
- `initialize` / `notifications/initialized` — handshake
- `tools/list` — returns all registered tool descriptors with JSON Schema
- `tools/call` — dispatches to the named tool handler
- `ping` — keepalive

---

## Decision 3: Authentication Strategy

**Decision**: Laravel Sanctum personal access tokens with `auth:sanctum` middleware on the MCP route.

**Rationale**: Sanctum is already installed via Breeze. Personal access tokens are the standard Laravel pattern for token-based API auth. The `auth:sanctum` guard handles Bearer tokens transparently alongside existing Livewire session authentication — no conflict. Tokens are hashed server-side; plaintext shown once at creation.

**Coexistence with Livewire**: Confirmed safe. `auth:sanctum` first checks session cookie (for Livewire), then falls back to Bearer token header (for MCP). No special casing needed.

**Alternatives considered**:
- Custom token table — unnecessary, Sanctum's `personal_access_tokens` is sufficient.
- OAuth2 (Passport) — overkill for a personal-use system; Sanctum simple tokens are appropriate.
- Hardcoded shared secret — does not scale to multiple users.

---

## Decision 4: Tool Registration Pattern

**Decision**: One Tool class per domain (ProjectTools, TicketTools, ExportTools) with methods decorated with `laravel/mcp` attributes.

**Rationale**: Each tool method receives the authenticated user (injected via `Auth::user()` inside the tool, since MCP middleware resolves the user before dispatching). Methods delegate directly to existing Action classes — no business logic lives in Tools.

**Tool-to-Action mapping**:

| MCP Tool | Action class |
|---|---|
| `list_projects` | direct Eloquent query |
| `create_project` | `CreateProject::handle()` |
| `update_project` | `UpdateProject::handle()` |
| `delete_project` | `DeleteProject::handle()` |
| `list_tickets` | direct Eloquent query |
| `create_ticket` | `CreateTicket::handle()` |
| `update_ticket` | `UpdateTicket::handle()` |
| `update_ticket_status` | `UpdateTicketStatus::handle()` |
| `delete_ticket` | `DeleteTicket::handle()` |
| `export_data` | `ExportUserData::handle()` |

---

## Decision 5: Token Management UI

**Decision**: New "API Tokens" section on the existing Profile page (new Livewire Volt component added to the profile page).

**Rationale**: Keeps surface area minimal per constitution Principle V (Simplicity & YAGNI). The profile page already handles password changes and account deletion — API token management fits naturally there.

**Required migrations**: Check if `personal_access_tokens` table exists; if not, publish Sanctum migrations. Add `HasApiTokens` trait to `User` model (may already be present from Breeze).

---

## Decision 6: Sanctum Setup Requirements

**Decision**: Enable Sanctum for token API use. Key steps:
1. Verify `laravel/sanctum` is installed (it is, via Breeze).
2. Verify `personal_access_tokens` migration has run — if not, publish and migrate.
3. Add `HasApiTokens` to `User` model.
4. No `statefulApi()` middleware needed — only Bearer token auth is used for the MCP endpoint (not SPA cookie mode).

**Note**: Token abilities/scopes are not used in v1 — all tokens grant full access to the owner's data. Can be added in a future iteration.

---

## Protocol Wire Format Reference

**tools/call request** (sent by MCP client):
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "method": "tools/call",
  "params": {
    "name": "create_ticket",
    "arguments": { "project_id": 1, "title": "Fix login bug", "priority": "high" }
  }
}
```

**tools/call response** (success):
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "result": {
    "content": [{ "type": "text", "text": "{\"id\":42,\"title\":\"Fix login bug\",...}" }]
  }
}
```

**tools/call response** (error):
```json
{
  "jsonrpc": "2.0",
  "id": 3,
  "result": {
    "content": [{ "type": "text", "text": "Error: project not found" }],
    "isError": true
  }
}
```
