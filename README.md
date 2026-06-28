# Audita MSC

Monorepo com backend Laravel, frontend Vue 3, PostgreSQL e Docker Compose.

## Estrutura

```
Audita MSC/
├── backend/          # API Laravel
├── frontend/         # SPA Vue 3 + TypeScript + Vue Router
├── docker/           # Dockerfile PHP e config Nginx
└── docker-compose.yml
```

## Pré-requisitos

- Docker
- Docker Compose v2 (`docker compose`) — se `docker-compose` v1 falhar, instale o plugin: `sudo apt install docker-compose-v2`

## Subir o ambiente

```bash
cp .env.example .env
./compose.sh up --build -d
```

Se preferir e tiver o plugin instalado:

```bash
docker compose up --build -d
```

**Importante:** suba os 4 serviços (`postgres`, `backend`, `nginx`, `frontend`). Se apenas backend/nginx estiverem rodando, a porta `5173` ficará indisponível.

Serviços:

| Serviço    | URL                      |
|-----------|---------------------------|
| Frontend  | http://localhost:5173     |
| Backend   | http://localhost:8000     |
| API health| http://localhost:8000/api/health |
| OpenAPI   | http://localhost:8000/docs/openapi/ |
| PostgreSQL| localhost:5432            |

## Comandos úteis

Rodar migrations:

```bash
./compose.sh exec backend php artisan migrate
```

Artisan:

```bash
./compose.sh exec backend php artisan <comando>
```

Logs:

```bash
./compose.sh logs -f frontend
```

Parar:

```bash
./compose.sh down
```

## Problemas comuns

### `http://localhost:5173` não abre

1. Verifique se o container do frontend está rodando:

```bash
docker ps --filter name=validamsc-frontend
```

2. Se não existir, suba o frontend:

```bash
./compose.sh up -d frontend
```

3. Veja os logs:

```bash
docker logs -f validamsc-frontend
```

## Variáveis de ambiente

Copie `.env.example` para `.env` na raiz do projeto. As credenciais padrão do PostgreSQL são:

- Banco: `validamsc`
- Usuário: `validamsc`
- Senha: `secret`

O frontend usa `VITE_API_URL` (padrão: `http://localhost:8000`).

## Stack

- **Backend:** Laravel 13, PHP 8.4, PostgreSQL 16
- **Frontend:** Vue 3, TypeScript, Vite, Vue Router, ESLint
- **Infra:** Docker Compose, Nginx, PHP-FPM
