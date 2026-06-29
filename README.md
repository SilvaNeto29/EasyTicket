# EasyTicket

Projeto de estudos em Laravel 12 — gerenciador de tickets com kanban board e integração MCP para uso com Claude Desktop.

## Features

- Kanban board com projetos e tickets (status: backlog, todo, in progress, done)
- **MCP Server** — o Claude Desktop consegue criar, listar e atualizar tickets via linguagem natural
- Laravel Pulse — dashboard de observabilidade em tempo real (requests, queries, exceptions)
- Autenticação completa com Laravel Breeze (Livewire + Volt)
- Personal Access Tokens via Sanctum para autenticação do MCP

## Quick Start

**Pré-requisito:** Docker

```bash
git clone <repo-url>
cd EasyTicket
docker compose up -d
```

Acesse [http://localhost:8080](http://localhost:8080) — crie uma conta e comece a usar.

> O primeiro build leva ~60s (Composer + assets dentro da imagem). A key do Laravel e as migrations rodam automaticamente no entrypoint.

## MCP Integration (Claude Desktop)

Com o app rodando:

1. Acesse **Profile → API Tokens** e gere um token
2. Adicione ao config do Claude Desktop (`~/.claude/claude_desktop_config.json`):

```json
{
  "mcpServers": {
    "easyticket": {
      "url": "http://localhost:8080/mcp",
      "headers": {
        "Authorization": "Bearer <seu-token>"
      }
    }
  }
}
```

3. Reinicie o Claude Desktop — as ferramentas aparecem: `create_project`, `create_ticket`, `list_tickets`, `update_ticket_status`, `export_data`, entre outras.

## Rodando os testes

```bash
docker compose exec app php artisan test
```

## Tech Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8.3, Laravel 12 |
| Frontend | Livewire 3 + Volt, Tailwind CSS, Vite |
| Auth | Laravel Breeze, Sanctum (Bearer tokens) |
| MCP | `laravel/mcp` (official first-party package) |
| Observabilidade | Laravel Pulse |
| Testes | Pest 3 (TDD na feature MCP) |
| Infra | Docker (php:8.3-fpm-alpine + Nginx), SQLite |
