# MCP Tool Contracts

**Protocol**: MCP 2025-06-18 (Streamable HTTP)
**Transport**: Single endpoint, authenticated via Bearer token (Sanctum)
**Base**: All tools require `Authorization: Bearer <token>` header.
**Error format**: All errors return `{ "content": [{ "type": "text", "text": "<message>" }], "isError": true }`

---

## Project Tools

### `list_projects`
Returns all projects belonging to the authenticated user.

**Input schema**: *(no arguments)*

**Output** (success):
```json
{
  "content": [{
    "type": "text",
    "text": "[{\"id\":1,\"name\":\"My Project\",\"description\":\"...\",\"color\":\"#3b82f6\",\"total_tickets\":5,\"open_tickets\":3,\"overdue_tickets\":1}]"
  }]
}
```

---

### `create_project`
Creates a new project owned by the authenticated user.

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "name":        { "type": "string", "minLength": 3, "maxLength": 255 },
    "description": { "type": "string" },
    "color":       { "type": "string", "pattern": "^#[0-9a-fA-F]{6}$" }
  },
  "required": ["name"]
}
```

**Output** (success): JSON of created project `{ id, name, description, color, created_at }`.
**Errors**: `name` missing → validation error. Name too short/long → validation error.

---

### `update_project`
Updates one or more fields of an existing project. Fields not provided remain unchanged.

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "project_id":  { "type": "integer" },
    "name":        { "type": "string", "minLength": 3, "maxLength": 255 },
    "description": { "type": "string" },
    "color":       { "type": "string", "pattern": "^#[0-9a-fA-F]{6}$" }
  },
  "required": ["project_id"]
}
```

**Output** (success): JSON of updated project.
**Errors**: `project_id` not found or belongs to another user → not-found error.

---

### `delete_project`
Permanently deletes a project and all its tickets. **Irreversible.**

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "project_id": { "type": "integer" }
  },
  "required": ["project_id"]
}
```

**Output** (success): `{ "deleted": true, "project_id": 1 }`
**Errors**: `project_id` not found or belongs to another user → not-found error.

---

## Ticket Tools

### `list_tickets`
Returns all tickets for a given project belonging to the authenticated user.

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "project_id": { "type": "integer" }
  },
  "required": ["project_id"]
}
```

**Output** (success): JSON array of tickets `[{ id, project_id, title, description, priority, status, due_date, is_overdue }]`.
**Errors**: `project_id` not found or belongs to another user → not-found error.
**Note**: Returns empty array `[]` when project exists but has no tickets (not an error).

---

### `create_ticket`
Creates a new ticket within a project.

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "project_id":  { "type": "integer" },
    "title":       { "type": "string", "minLength": 3, "maxLength": 255 },
    "description": { "type": "string" },
    "priority":    { "type": "string", "enum": ["low", "medium", "high", "critical"] },
    "status":      { "type": "string", "enum": ["backlog", "todo", "in_progress", "in_review", "done", "cancelled"] },
    "due_date":    { "type": "string", "format": "date" }
  },
  "required": ["project_id", "title"]
}
```

**Defaults**: `priority` = `medium`, `status` = `backlog`.
**Output** (success): JSON of created ticket.
**Errors**: Missing required fields, invalid enum values, or project not owned by user → error.

---

### `update_ticket`
Updates one or more fields of a ticket. Fields not provided remain unchanged.

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "ticket_id":   { "type": "integer" },
    "title":       { "type": "string", "minLength": 3, "maxLength": 255 },
    "description": { "type": "string" },
    "priority":    { "type": "string", "enum": ["low", "medium", "high", "critical"] },
    "due_date":    { "type": "string", "format": "date" }
  },
  "required": ["ticket_id"]
}
```

**Output** (success): JSON of updated ticket.
**Errors**: Ticket not found or belongs to another user → not-found error.

---

### `update_ticket_status`
Moves a ticket to a new status column.

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "ticket_id":  { "type": "integer" },
    "new_status": {
      "type": "string",
      "enum": ["backlog", "todo", "in_progress", "in_review", "done", "cancelled"]
    }
  },
  "required": ["ticket_id", "new_status"]
}
```

**Output** (success): JSON of updated ticket with new status.
**Errors**: Invalid status value → descriptive validation error. Ticket not owned → not-found error.

---

### `delete_ticket`
Permanently deletes a ticket. **Irreversible.**

**Input schema**:
```json
{
  "type": "object",
  "properties": {
    "ticket_id": { "type": "integer" }
  },
  "required": ["ticket_id"]
}
```

**Output** (success): `{ "deleted": true, "ticket_id": 42 }`
**Errors**: Ticket not found or belongs to another user → not-found error.

---

## Export Tool

### `export_data`
Returns the complete dataset for the authenticated user (all projects and all their tickets).

**Input schema**: *(no arguments)*

**Output** (success): JSON matching the browser export format:
```json
{
  "content": [{
    "type": "text",
    "text": "[{\"id\":1,\"name\":\"Project\",\"tickets\":[{\"id\":1,\"title\":\"...\"}]}]"
  }]
}
```

---

## Token Management (UI only — not an MCP tool)

Managed through the EasyTicket web interface (`/profile`). No MCP endpoint for token CRUD — tokens are generated and revoked in the browser only.

| Action | Livewire method | Description |
|---|---|---|
| Generate token | `createToken(name)` | Returns plaintext once; stores hash |
| List tokens | computed property | Shows name + created_at (no values) |
| Revoke token | `revokeToken(id)` | Deletes row from personal_access_tokens |
