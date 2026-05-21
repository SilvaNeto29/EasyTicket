# Data Model: MCP Server with Sanctum Authentication

**Feature**: `002-mcp-sanctum-tools`

---

## New Entity: PersonalAccessToken

Provided by Laravel Sanctum — uses the `personal_access_tokens` table (published via `vendor:publish --tag=sanctum-migrations`).

| Field | Type | Notes |
|---|---|---|
| `id` | bigint PK | auto-increment |
| `tokenable_type` | string | polymorphic (`App\Models\User`) |
| `tokenable_id` | bigint | FK → users.id |
| `name` | string | user-provided label (e.g. "Claude Desktop") |
| `token` | string(64) | SHA-256 hash of the plaintext token |
| `abilities` | JSON nullable | not used in v1 (all tokens are full-access) |
| `last_used_at` | timestamp nullable | updated on each authenticated request |
| `expires_at` | timestamp nullable | not used in v1 (no expiry) |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

**Constraints**:
- `name` is required and must not be empty
- No uniqueness constraint on name per user (multiple tokens can share a name)
- `tokenable_type` is always `App\Models\User` in this project

**No new migrations needed** beyond publishing Sanctum's migration (if not already run).

---

## Modified Entity: User

Add `HasApiTokens` trait (from `Laravel\Sanctum\HasApiTokens`) to `app/Models/User.php`.

| Addition | Detail |
|---|---|
| `HasApiTokens` trait | Enables `$user->createToken()`, `$user->tokens()`, `$user->currentAccessToken()` |

No schema change to the `users` table.

---

## Relationships

```
User (1) ──── (N) PersonalAccessToken
```

- A user can have zero or more tokens.
- Each token belongs to exactly one user.
- Revoking a token deletes the row from `personal_access_tokens`.

---

## No Other Schema Changes

The existing `projects` and `tickets` tables are unchanged. The MCP tools read and write to them via the existing Action classes, which already enforce user ownership.

---

## Existing Entities (unchanged, for reference)

| Entity | Key Fields | Owned By |
|---|---|---|
| `Project` | id, user_id, name, description, color | User |
| `Ticket` | id, project_id, user_id, title, description, priority, status, due_date | User |
