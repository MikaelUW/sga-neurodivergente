<div align="center">

# 🧩 SGA Neurodivergente

### Sistema de Gestão de Alunos Neurodivergentes

Plataforma completa para gestão de alunos neurodivergentes — cadastros, planos de manejo comportamental/operacional, laudos médicos e registro de ocorrências, com foco em **segurança de dados** e conformidade com a **LGPD**.

![Laravel](https://img.shields.io/badge/Laravel-11.x-FF2D20?logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)
![LGPD](https://img.shields.io/badge/LGPD-AES--256-2E7D32)

</div>

---

## 📖 Sobre o projeto

O **SGA Neurodivergente** foi criado para apoiar escolas no acompanhamento de alunos neurodivergentes (TEA, TDAH e outros), organizando informações sensíveis de forma segura e centralizada. Todos os dados pessoais são **criptografados com AES-256** antes de serem gravados no banco, garantindo conformidade com a LGPD.

O projeto está organizado como um **monorepo** com duas partes independentes:

| Camada | Pasta | Stack |
|--------|-------|-------|
| 🎨 Front-end | [`frontend/`](./frontend) | HTML + CSS + JavaScript (protótipo de interface) |
| ⚙️ Back-end (API) | [`backend/`](./backend) | Laravel 11 · PHP 8.3 · PostgreSQL 16 · Docker |

---

## 🗂️ Estrutura do repositório

```
sga-neurodivergente/
├── frontend/                 # Interface do usuário (protótipo)
│   ├── index.html
│   ├── css/style.css
│   └── js/script.js
│
├── backend/                  # API RESTful em Laravel
│   ├── app/                  # Controllers, Models, Services (criptografia LGPD)
│   ├── database/             # Migrations e Seeders
│   ├── docker/               # Dockerfile, Nginx e entrypoint
│   ├── routes/ · resources/ · tests/
│   ├── docker-compose.yml
│   └── openapi.json          # Especificação OpenAPI 3.0
│
├── .gitignore
└── README.md
```

---

## 🚀 Como executar

### Pré-requisitos
- [Docker](https://www.docker.com/) e Docker Compose
- Um navegador web (para o front-end)

### 1. Back-end (API)

```bash
cd backend
# 1) Crie o .env e gere a APP_KEY (uma única vez)
cp .env.example .env
docker compose run --rm app php artisan key:generate --show
#   copie o valor para APP_KEY= no arquivo .env

# 2) Suba o ambiente
docker compose up -d --build
```

O `entrypoint.sh` valida a `APP_KEY`, espera o PostgreSQL (com timeout), roda `migrate` e, **fora de produção**, `db:seed`. A API fica em **http://localhost:8000** (porta ajustável via `APP_PORT`).

> **APP_KEY é crítica:** ela cifra os dados sensíveis. Mantenha-a fixa e inclua-a no backup. Trocá-la torna os dados existentes ilegíveis. O entrypoint **não** regenera a chave automaticamente.

**Produção:** use `cp .env.production.example .env`, preencha os segredos e suba só o arquivo base (sem o override de dev):

```bash
docker compose -f docker-compose.yml up -d --build
```

### 2. Front-end

Basta abrir o arquivo `frontend/index.html` no navegador, ou servir a pasta com qualquer servidor estático:

```bash
cd frontend
# opção rápida com Python
python -m http.server 5500
```

---

## 🔒 Segurança e LGPD

Dados sensíveis dos alunos (nome, necessidades especiais, ocorrências e planos de manejo) são cifrados automaticamente com **AES-256-CBC** pela camada de modelo (`CriptografiaService`) antes de irem para o banco, e decifrados apenas para usuários autenticados e autorizados.

> ⚠️ O arquivo `backend/.env` **não é versionado** (contém segredos). Use o `backend/.env.example` como base ao configurar o ambiente.

---

## 🔑 Credenciais padrão (seeders)

| Perfil | Login | Senha |
|--------|-------|-------|
| Administrador | `admin@escola.edu.br` | `admin123` |
| Monitora | `monitora@escola.edu.br` | `monitora123` |

> Os seeders só rodam fora de produção. Em produção, crie o primeiro administrador manualmente com senha forte.

---

## 🧪 Testes

**Backend (PHPUnit)** — roda em SQLite em memória, sem tocar no banco real:

```bash
cd backend
docker compose exec app php artisan test
```

A suíte (29 testes) cobre autenticação, cadastro de alunos, manejo operacional, ocorrências criptografadas, upload/download de laudos em PDF, gestão de permissões, blind index de busca, paginação e trilha de auditoria LGPD.

**Frontend (E2E com Playwright)** — exige a API no ar (`docker compose up` no backend):

```bash
cd frontend
npm install
npx playwright install chromium
npm run test:e2e
```

Os 7 testes E2E exercitam as telas reais contra a API: login (admin/monitora), login inválido, logout, dashboard com dados reais, cadastro de aluno + protocolo e registro/consulta de ocorrência criptografada.

## 🖥️ Front-end (desenvolvimento)

```bash
cd frontend
npm run serve   # serve em http://localhost:5500
```

O front consome a API em `http://localhost:8000/api` (ajustável definindo `window.SGA_API_BASE`). O login usa token Sanctum (Bearer), armazenado no `localStorage`.

---

## 📚 Documentação adicional

- 📄 [`backend/README.md`](./backend/README.md) — detalhes completos da API, exemplos de requisições (cURL) e casos de uso.
- 📄 [`backend/openapi.json`](./backend/openapi.json) — especificação OpenAPI 3.0 (Swagger).
- 📄 [`backend/LGPD_PESQUISA_E_AUDITORIA.md`](./backend/LGPD_PESQUISA_E_AUDITORIA.md) — notas de conformidade e auditoria.

---

<div align="center">

Feito com 💜 para tornar a educação mais inclusiva.

</div>
