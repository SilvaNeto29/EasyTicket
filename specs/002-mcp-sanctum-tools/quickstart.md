# MCP Quickstart — Connecting Claude Desktop to EasyTicket

## Prerequisites

- EasyTicket running at `http://localhost:8080` (Docker: `docker compose up -d`)
- Claude Desktop installed

## Step 1: Generate an API Token

1. Open EasyTicket in your browser: `http://localhost:8080`
2. Log in and navigate to **Profile** (top-right menu)
3. Scroll to the **API Tokens** section
4. Enter a name (e.g. `Claude Desktop`) and click **Generate**
5. Copy the token immediately — it is shown only once

## Step 2: Configure Claude Desktop

Edit Claude Desktop's config file:

- **macOS**: `~/Library/Application Support/Claude/claude_desktop_config.json`
- **Windows**: `%APPDATA%\Claude\claude_desktop_config.json`

Add the following (replace `<YOUR_TOKEN>` with the token from Step 1):

```json
{
  "mcpServers": {
    "easyticket": {
      "type": "http",
      "url": "http://localhost:8080/mcp",
      "headers": {
        "Authorization": "Bearer <YOUR_TOKEN>"
      }
    }
  }
}
```

## Step 3: Restart Claude Desktop

Quit and reopen Claude Desktop. The EasyTicket tools will appear in the tool picker.

## Available Tools

| Tool | What it does |
|---|---|
| `list_projects` | List all your projects |
| `create_project` | Create a new project |
| `update_project` | Rename or update a project |
| `delete_project` | Permanently delete a project and all its tickets |
| `list_tickets` | List tickets in a project |
| `create_ticket` | Create a new ticket |
| `update_ticket` | Update ticket fields |
| `update_ticket_status` | Move a ticket to a new status |
| `delete_ticket` | Permanently delete a ticket |
| `export_data` | Export all your projects and tickets as JSON |

## Security Notes

- Each token operates as the user who created it — no cross-user access is possible
- Revoke tokens anytime from the Profile page
- The `/mcp` endpoint returns 401 for any request without a valid token
