# Sistema de Gestão de Alunos Neurodivergentes (SGAN API)

Backend API RESTful em **Laravel 11** com infraestrutura **Docker** (PHP-FPM + Nginx + PostgreSQL) desenvolvida para gerenciar cadastros, planos de manejo operacional/comportamental, laudos médicos em PDF e registro de ocorrências criptografadas para alunos neurodivergentes.

---

## 🚀 Arquitetura e Tecnologias

- **Framework**: Laravel 11.x (PHP 8.3)
- **Servidor Web**: Nginx (alpine)
- **Banco de Dados**: PostgreSQL 16 (Alpine)
- **Autenticação**: Laravel Sanctum (Tokens Bearer)
- **Segurança & LGPD**: Criptografia automática de atributos sensíveis via Eloquent Attribute Accessors/Mutators (`CriptografiaService`).
- **Contêineres**: Docker & Docker Compose

---

## 🔒 Segurança e Criptografia de Dados (LGPD)

Todos os dados pessoais e sensíveis dos alunos (nome, necessidades especiais, relatos de ocorrências e manejo) são automaticamente cifrados com **AES-256-CBC** antes de serem salvos no banco de dados.

Ao consultar a API com um usuário autenticado e autorizado (Administrador ou Monitora), os dados são automaticamente decifrados pela camada de modelo (`Aluno` e `Ocorrencia`).

---

## 📁 Estutura do Projeto

```
sgan-api/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/   # Auth, Aluno, InfoOperacional, Ocorrencia, LaudoMedico, Permissao
│   │   └── Middleware/         # CheckPerfil (Role authorization)
│   ├── Models/                # User, Administrador, Monitora, Aluno, InfoOperacional, Ocorrencia, LaudoMedico
│   └── Services/              # CriptografiaService (Crypt::encryptString / decryptString)
├── database/
│   ├── migrations/            # Tabelas relacionais do PostgreSQL
│   └── seeders/               # Dados iniciais para teste (Admin, Monitora, Aluno de exemplo)
├── docker/
│   ├── Dockerfile             # PHP 8.3-FPM + Extensões (pdo_pgsql, gd, zip, intl, etc)
│   ├── entrypoint.sh          # Instalação automática de vendor, migrations, seeders e storage:link
│   └── nginx/default.conf     # Configuração Nginx para escutar na porta 8000
├── tests/
│   ├── Unit/                  # Testes unitários do CriptografiaService
│   └── Feature/               # Testes de integração dos Casos de Uso UC01 a UC09
├── docker-compose.yml
├── openapi.json               # Especificação Swagger / OpenAPI 3.0
└── phpunit.xml
```

---

## ⚡ Como Executar o Projeto com Docker

### 1. Iniciar os Contêineres

Na raiz do diretório `sgan-api`, execute:

```bash
docker compose up -d --build
```

O script de inicialização (`entrypoint.sh`) irá automaticamente:
1. Executar `composer install` caso a pasta `vendor` não exista.
2. Copiar `.env.example` para `.env` e gerar a `APP_KEY`.
3. Aguardar a prontidão do banco PostgreSQL.
4. Executar as migrações (`php artisan migrate`).
5. Executar os seeders (`php artisan db:seed`).
6. Criar o link simbólico para downloads de arquivos (`php artisan storage:link`).

---

## 🧪 Como Executar a Suíte de Testes (PHPUnit)

Para rodar todos os testes automatizados da API via Docker:

```bash
docker compose exec app php artisan test
```

A suíte inclui:
- **Unit**: Validação do serviço de criptografia.
- **Feature**:
  - `AuthTest`: UC01 - Autenticação, emissão e revogação de tokens.
  - `AlunoTest`: UC02 & UC04 - Cadastro, atualização e consulta de perfil.
  - `InfoOperacionalTest`: UC03 - Manejo comportamental e regras operacionais.
  - `OcorrenciaTest`: UC05 & UC06 - Registro com criptografia em DB e histórico decifrado.
  - `LaudoMedicoTest`: UC09 - Upload e download seguro de arquivos PDF.
  - `PermissaoTest`: UC07 - Gerenciamento de acessos de administradores.

---

## 🔑 Credenciais Padrão (Seeders)

### Administrador
- **Login**: `admin@escola.edu.br`
- **Senha**: `admin123`
- **Perfil**: `admin`

### Monitora
- **Login**: `monitora@escola.edu.br`
- **Senha**: `monitora123`
- **Perfil**: `monitora`

---

## 📌 Exemplos de Requisições (cURL)

### 1. Autenticação (Login)
```bash
curl -X POST http://localhost:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin@escola.edu.br","password":"admin123"}'
```

### 2. Cadastrar Aluno (Admin)
```bash
curl -X POST http://localhost:8000/api/alunos \
  -H "Authorization: Bearer <SEU_TOKEN>" \
  -H "Content-Type: application/json" \
  -d '{
    "nome": "Lucas Santos",
    "data_nascimento": "2016-04-15",
    "necessidades_especiais": "TEA Nível 1, TDAH",
    "observacoes_gerais": "Sensível a ruídos em ambientes fechados"
  }'
```

### 3. Registrar Ocorrência Comportamental (Monitora)
```bash
curl -X POST http://localhost:8000/api/ocorrencias \
  -H "Authorization: Bearer <TOKEN_MONITORA>" \
  -H "Content-Type: application/json" \
  -d '{
    "aluno_id": 1,
    "descricao_fatos": "Apresentou agitação durante o intervalo devido a som elevado",
    "tipo_intervencao": "Direcionamento calmo para sala de descompressão e abafador acústico"
  }'
```

### 4. Consultar Histórico Decifrado do Aluno
```bash
curl -X GET http://localhost:8000/api/alunos/1/ocorrencias \
  -H "Authorization: Bearer <SEU_TOKEN>"
```

### 5. Upload de Laudo Médico PDF (Admin)
```bash
curl -X POST http://localhost:8000/api/alunos/1/laudos \
  -H "Authorization: Bearer <TOKEN_ADMIN>" \
  -F "arquivo=@/caminho/para/laudo.pdf" \
  -F "crm_medico=CRM/SP 123456" \
  -F "especialidade=Neuropediatria"
```
