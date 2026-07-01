# Audita MSC

Monorepo com backend Laravel, frontend Vue 3, PostgreSQL e Docker Compose.

## Estrutura

```
Audita MSC/
├── backend/          # API Laravel
├── frontend/         # SPA Vue 3 + TypeScript + Vue Router
├── docker/           # Dockerfile PHP e config Nginx
├── compose.sh        # Wrapper para docker compose v2
└── docker-compose.yml
```

## Pré-requisitos

- Docker Engine
- Docker Compose v2 (`docker compose`) — se `docker-compose` v1 falhar, instale o plugin:

```bash
sudo apt install docker-compose-v2
```

- Permissão para usar o Docker (se necessário, adicione seu usuário ao grupo `docker` e faça login novamente):

```bash
sudo usermod -aG docker "$USER"
```

## Primeira execução

### 1. Variáveis de ambiente

Na raiz do projeto:

```bash
cp .env.example .env
```

Esse arquivo controla portas e credenciais do PostgreSQL usadas pelo `docker-compose.yml`.

O backend cria `backend/.env` automaticamente a partir de `backend/.env.example` na primeira subida do container e gera o `APP_KEY` se estiver vazio. As credenciais de banco em `backend/.env` devem coincidir com as da raiz (já vêm alinhadas nos exemplos).

### 2. Subir os containers

```bash
chmod +x compose.sh
./compose.sh up --build -d
```

Alternativa, se o plugin v2 estiver instalado:

```bash
docker compose up --build -d
```

**Importante:** suba os 4 serviços (`postgres`, `backend`, `nginx`, `frontend`). Na primeira execução o frontend roda `npm install` e pode levar cerca de 1–2 minutos para ficar disponível.

Verifique se tudo subiu:

```bash
./compose.sh ps
```

### 3. Banco de dados

Após o PostgreSQL ficar saudável, rode migrations e seed (regras MSC):

```bash
./compose.sh exec backend php artisan migrate --seed
```

### 4. Criar SuperAdmin

Forma interativa:

```bash
./compose.sh exec backend php artisan aura:create-superadmin
```

Forma não interativa (defina em `backend/.env` antes de rodar):

```env
AURA_SUPERADMIN_NAME=Admin
AURA_SUPERADMIN_EMAIL=admin@example.com
AURA_SUPERADMIN_PASSWORD=senha-segura
```

```bash
./compose.sh exec backend php artisan aura:create-superadmin
```

Acesse o painel administrativo em http://localhost:5173/admin com esse usuário.

### 5. Fluxo de leads (trial → aprovação)

Leads enviados em `/solicitar-demonstracao` ficam com status `pending`. O SuperAdmin provisiona trial em **Admin → Leads** ou via CLI:

```bash
# Listar UUID do lead (via Admin → Leads ou banco)
./compose.sh exec backend php artisan aura:lead-start-trial {uuid-do-lead}
```

O trial:
- cria o município (`municipalities`) a partir do IBGE/nome do lead;
- cria o usuário municipal vinculado;
- permite **importações ilimitadas** durante o período de teste;
- expira em **7 dias** (`LEAD_TRIAL_DAYS`), desativando a conta automaticamente.

Comandos do fluxo:

```bash
./compose.sh exec backend php artisan aura:lead-start-trial {uuid}   # pending → trial
./compose.sh exec backend php artisan aura:lead-approve {uuid}     # trial → approved (acesso definitivo)
./compose.sh exec backend php artisan aura:lead-fail {uuid}        # pending/trial → failed
./compose.sh exec backend php artisan aura:expire-trials           # expira trials vencidos
```

Status dos leads: `pending` | `trial` | `approved` | `failed`.

Para expiração automática em produção, configure o scheduler do Laravel (`php artisan schedule:work` ou cron com `schedule:run`).

### 6. (Opcional) Usuário municipal manual para testes

Alternativa ao fluxo de lead — crie município e usuário diretamente:

```bash
./compose.sh exec backend php artisan tinker --execute="
\$municipio = \App\Models\Municipality::firstOrCreate(
    ['ibge_code' => '2507507'],
    ['name' => 'João Pessoa']
);
\App\Models\User::factory()->create([
    'name' => 'Contador Teste',
    'email' => 'contador@example.com',
    'municipality_id' => \$municipio->id,
]);
echo \"Usuário criado: contador@example.com / password\n\";
"
```

Login municipal: http://localhost:5173/login (senha padrão da factory: `password`).

### 7. Validar que a API responde

```bash
curl -s http://localhost:8000/api/health
```

Resposta esperada: JSON com `"status": "ok"`.

## Serviços

| Serviço    | URL                                      |
|-----------|-------------------------------------------|
| Frontend  | http://localhost:5173                     |
| Login     | http://localhost:5173/login               |
| Admin     | http://localhost:5173/admin               |
| Backend   | http://localhost:8000                     |
| API health| http://localhost:8000/api/health          |
| OpenAPI   | http://localhost:8000/docs/openapi/       |
| PostgreSQL| localhost:`POSTGRES_PORT` (padrão: 5432)  |

## Comandos úteis

Artisan (qualquer comando):

```bash
./compose.sh exec backend php artisan <comando>
```

Rodar apenas migrations:

```bash
./compose.sh exec backend php artisan migrate
```

Rodar testes do backend:

```bash
./compose.sh exec backend php artisan test
```

Logs de um serviço:

```bash
./compose.sh logs -f frontend
./compose.sh logs -f backend
```

Reconstruir imagens após mudanças no Dockerfile:

```bash
./compose.sh up --build -d
```

Parar containers:

```bash
./compose.sh down
```

Parar e apagar volume do banco (reset completo):

```bash
./compose.sh down -v
```

Depois repita migrate/seed e a criação do SuperAdmin.

## Desenvolvimento sem Docker (opcional)

Requer PHP 8.4+, Composer, Node.js 22+, PostgreSQL 16.

**Backend:**

```bash
cd backend
cp .env.example .env
composer install
php artisan key:generate
# Ajuste DB_* no .env para seu PostgreSQL local
php artisan migrate --seed
php artisan serve
```

**Frontend** (em outro terminal):

```bash
cd frontend
cp .env.example .env
npm install
npm run dev
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

3. Veja os logs (aguarde o `npm install` na primeira subida):

```bash
docker logs -f validamsc-frontend
```

### Erro de conexão com o banco / migrate falha

1. Confirme que o PostgreSQL está saudável:

```bash
./compose.sh ps postgres
```

2. Verifique se as credenciais em `.env` (raiz) batem com `backend/.env`:

- Banco: `validamsc`
- Usuário: `validamsc`
- Senha: `secret`

3. Se alterou credenciais com containers já criados, pode ser necessário recriar o volume:

```bash
./compose.sh down -v
./compose.sh up --build -d
./compose.sh exec backend php artisan migrate --seed
```

### Upload MSC rejeitado por município

O arquivo importado precisa ter o mesmo código IBGE do município vinculado à conta do usuário logado. Confira o município em **Configurações** ou na etiqueta **Ambiente de validação** na tela de importação.

## Variáveis de ambiente

### Raiz (`.env`)

Usado pelo Docker Compose:

| Variável        | Padrão                  | Descrição                    |
|----------------|-------------------------|------------------------------|
| `POSTGRES_DB`  | `validamsc`             | Nome do banco                |
| `POSTGRES_USER`| `validamsc`             | Usuário do PostgreSQL        |
| `POSTGRES_PASSWORD` | `secret`           | Senha do PostgreSQL          |
| `POSTGRES_PORT`| `5432`                  | Porta exposta do PostgreSQL  |
| `BACKEND_PORT` | `8000`                  | Porta da API (Nginx)         |
| `FRONTEND_PORT`| `5173`                  | Porta do Vite                |
| `VITE_API_URL` | `http://localhost:8000` | URL da API no frontend       |

### Backend (`backend/.env`)

Criado automaticamente no container. Principais variáveis:

- `APP_KEY` — gerado automaticamente se vazio
- `DB_*` — devem coincidir com o PostgreSQL do Compose
- `AURA_SUPERADMIN_*` — criação não interativa do SuperAdmin
- `LEAD_ADMIN_EMAIL` — e-mail que recebe notificações de leads

### Frontend (`frontend/.env`)

Necessário apenas fora do Docker. No Compose, `VITE_API_URL` vem da raiz.

## Stack

- **Backend:** Laravel 13, PHP 8.4, PostgreSQL 16
- **Frontend:** Vue 3, TypeScript, Vite, Vue Router, Pinia
- **Infra:** Docker Compose, Nginx, PHP-FPM
