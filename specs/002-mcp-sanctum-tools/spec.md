# Feature Specification: MCP Server with Sanctum Authentication

**Feature Branch**: `002-mcp-sanctum-tools`

**Created**: 2026-05-21

**Status**: Draft

---

## Overview

EasyTicket users need to interact with their projects and tickets through AI assistants (such as Claude Desktop) via the Model Context Protocol (MCP). This feature adds an MCP server to EasyTicket, allowing any MCP-compatible client to manage projects and tickets programmatically — authenticated via a personal access token that each user generates in their profile.

**Why**: Users want to create tickets, update statuses, and query their projects without switching to the browser. AI assistants can then act as a voice or natural-language interface to EasyTicket.

---

## Actors

- **Authenticated User**: Any EasyTicket account holder who has generated a personal access token.
- **MCP Client**: An external AI assistant or automation tool (e.g., Claude Desktop) acting on behalf of the user.

---

## User Stories

### US1 — Token Management (P1)
As a user, I want to generate and revoke personal access tokens in my profile settings, so that I can connect MCP clients to my account securely.

- Generate a named token (e.g., "Claude Desktop")
- View the token value once immediately after generation
- Revoke any active token at any time
- Multiple tokens can coexist (one per device/client)

### US2 — MCP Tool: Projects (P1)
As an MCP client acting on behalf of a user, I want to list, create, update, and delete that user's projects, so that the user can manage projects through an AI assistant.

- `list_projects` — returns all projects belonging to the authenticated user
- `create_project` — creates a new project (name required; description and color optional)
- `update_project` — updates name, description, or color of an existing project
- `delete_project` — deletes a project and all its tickets (irreversible)

### US3 — MCP Tool: Tickets (P1)
As an MCP client acting on behalf of a user, I want to list, create, update, update status, and delete tickets within a project, so that the user can manage tickets through an AI assistant.

- `list_tickets` — returns all tickets for a given project
- `create_ticket` — creates a ticket (title required; description, priority, due_date, status optional)
- `update_ticket` — updates title, description, priority, or due date
- `update_ticket_status` — moves a ticket to a new status column
- `delete_ticket` — deletes a ticket permanently

### US4 — MCP Tool: Export (P2)
As an MCP client, I want to export all the user's data as structured JSON, so that the user can back up or analyze their data programmatically.

- `export_data` — returns the complete dataset (all projects and tickets)

### US5 — Security & Isolation (P1, cross-cutting)
As a user, I need my data to be invisible to other users' tokens, so that multi-user deployments remain secure.

- Each tool call authenticates via Bearer token
- A token grants access only to the data of the user who created it
- Invalid or missing tokens receive an authentication error, not a data response
- A user cannot access another user's projects or tickets via any token

---

## Functional Requirements

### Token Management
- FR-001: Users can generate a named personal access token from their profile settings page.
- FR-002: The token value is shown exactly once at creation time; it cannot be retrieved again.
- FR-003: Users can view a list of their active tokens (name and creation date, not the value).
- FR-004: Users can revoke any of their tokens individually.
- FR-005: A user may hold multiple active tokens simultaneously.

### MCP Endpoint
- FR-006: The system exposes an MCP-compatible endpoint that accepts tool-call requests.
- FR-007: Every request must include a valid Bearer token in the Authorization header.
- FR-008: Requests with missing, invalid, or revoked tokens receive an authentication error.
- FR-009: All tools operate exclusively on data belonging to the token's owner.

### Project Tools
- FR-010: `list_projects` returns id, name, description, color, and ticket counts for each project.
- FR-011: `create_project` validates that name is present and between 3–255 characters; color must be a valid hex value if provided.
- FR-012: `update_project` applies partial updates; fields not included remain unchanged.
- FR-013: `delete_project` cascades deletion to all tickets in the project.

### Ticket Tools
- FR-014: `list_tickets` accepts a project_id and returns all tickets with their fields and current status.
- FR-015: `create_ticket` validates title (required, 3–255 chars); priority defaults to `medium`; status defaults to `backlog`.
- FR-016: `update_ticket` applies partial updates to title, description, priority, or due date.
- FR-017: `update_ticket_status` accepts only valid status values; rejects invalid transitions with a descriptive error.
- FR-018: `delete_ticket` permanently removes the ticket.

### Export Tool
- FR-019: `export_data` returns the same structure as the browser-based JSON export.

### Error Handling
- FR-020: Tool calls with missing required fields return a descriptive validation error identifying the missing field.
- FR-021: Tool calls referencing a resource that does not exist (or belongs to another user) return a not-found error — never another user's data.
- FR-022: All errors follow a consistent structure that MCP clients can parse.

---

## User Scenarios & Testing

### Scenario 1: First-time token setup
1. User logs into EasyTicket in browser
2. User navigates to Profile → API Tokens
3. User enters token name "Claude Desktop" and clicks Generate
4. System displays the token value — user copies it
5. User configures Claude Desktop with the token
6. Claude Desktop connects and can list the user's projects

### Scenario 2: Successful tool call flow
1. MCP client sends `list_projects` with valid Bearer token
2. System authenticates token, identifies owner
3. System returns only that owner's projects
4. Client sends `create_ticket` with project_id and title
5. System creates ticket, returns created ticket data
6. Client sends `update_ticket_status` to move ticket to "in_progress"
7. Status updates correctly; change is visible in the browser

### Scenario 3: Security boundary — cross-user attempt
1. User A has token A; User B has token B
2. User A's MCP client calls `list_tickets` with project_id belonging to User B
3. System returns not-found error (same response as if project didn't exist)
4. User A's client cannot enumerate or access User B's data

### Scenario 4: Token revocation
1. User revokes a token from the profile page
2. MCP client using the revoked token sends a tool call
3. System responds with authentication error
4. No data is returned

### Scenario 5: Validation error
1. MCP client calls `create_project` with an empty name
2. System returns a validation error identifying the problem
3. No project is created

### Edge Cases
- Calling `delete_project` on a project with many tickets: all cascade correctly
- Calling `list_tickets` on a project with zero tickets: returns empty list (not an error)
- Calling `update_ticket_status` with an invalid status value: returns descriptive error
- Multiple simultaneous token holders for the same user: each works independently
- Token name not provided on creation: system rejects with validation error

---

## Success Criteria

### Measurable Outcomes
- A user can connect an MCP client to EasyTicket in under 2 minutes (token generation to first successful tool call).
- All 10 MCP tools execute successfully when called with a valid token.
- Tool calls with invalid tokens are rejected 100% of the time — no data leakage.
- A tool call for a resource belonging to another user returns an error, never that resource's data.
- Revoking a token immediately prevents further access (no grace period).

### Quality Bar
- All new functionality is covered by automated tests including adversarial cases (wrong token, other user's data, invalid inputs, revoked tokens).
- No existing tests regress.
- The MCP endpoint is documented sufficiently that a new MCP client can integrate without additional guidance.

---

## Key Entities

### PersonalAccessToken
- `id`: integer
- `user_id`: foreign key → User
- `name`: string (e.g., "Claude Desktop")
- `token`: hashed value stored server-side; plaintext shown once
- `last_used_at`: timestamp (nullable)
- `created_at`: timestamp

---

## Out of Scope

- OAuth2 flows or third-party app authorization
- Token expiry dates or automatic rotation (manual revocation only)
- Rate limiting per token (may be added in a future iteration)
- MCP streaming or server-sent events
- Webhook notifications triggered by MCP tool calls
- Email notifications deferred from the main spec remain deferred

---

## Assumptions

- The MCP client handles JSON-RPC framing; the server need only implement the tool-call request/response contract.
- A single `/mcp` endpoint handles all tool dispatching (no per-tool routes).
- Sanctum personal access tokens are the authentication mechanism; no separate API key table is needed.
- Token names are user-defined labels for their own reference; uniqueness per user is not enforced.
- The existing Action classes (CreateProject, UpdateProject, etc.) are called directly by the MCP dispatcher with the authenticated user injected — no HTTP request object involved.

---

## Dependencies

- Existing EasyTicket Action classes (already implemented and tested).
- Laravel Sanctum (already available via Laravel; needs enabling for token API).
- MCP protocol implementation: prefer an existing PHP/Laravel package if mature and actively maintained; otherwise implement the JSON-RPC 2.0 tool-call contract directly.

---

## Clarifications

### Session 2026-05-21
- Q: Should token generation UI be integrated into the existing Profile page or a separate Settings page? → A: Integrated into the existing Profile/settings page as a new "API Tokens" section — keeps the surface area minimal.
- Q: Should `list_tickets` support filtering by status or priority via the tool call? → A: No filtering in v1 — return all tickets for the project; clients can filter client-side. Keeps the tool interface simple.
- Q: What happens when an MCP client calls `delete_project` — should there be a confirmation step? → A: No confirmation in the MCP tool — the caller is responsible. The tool description must clearly state the action is irreversible and cascades to tickets.
