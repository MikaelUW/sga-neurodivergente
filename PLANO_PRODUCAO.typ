// =====================================================================
//  PLANO DE PRODUÇÃO — SGA Neurodivergente
//  Gap analysis: o que falta implementar e corrigir para produção
// =====================================================================

#let azul      = rgb("#1771BF")
#let verde     = rgb("#09E551")
#let amarelo   = rgb("#FAC51F")
#let vermelho  = rgb("#F21D1D")
#let cinza     = rgb("#F2EAE2")
#let preto     = rgb("#00131C")
#let fundo     = rgb("#FAFAFA")
#let azulclaro = rgb("#E8F1FA")

#set page(paper: "a4", margin: (x: 2.2cm, y: 2.2cm), numbering: "1", number-align: center)
#set text(font: ("Segoe UI", "Arial"), size: 10.5pt, lang: "pt", fill: preto)
#set par(justify: true, leading: 0.75em, spacing: 1.0em)
#set heading(numbering: "1.1  ")

#show heading.where(level: 1): it => { set text(size: 18pt, weight: "bold", fill: azul); block(above: 1.4em, below: 0.7em)[#it] }
#show heading.where(level: 2): it => { set text(size: 13.5pt, weight: "bold", fill: azul); block(above: 1.1em, below: 0.5em)[#it] }
#show heading.where(level: 3): it => { set text(size: 11.5pt, weight: "bold", fill: preto.lighten(10%)); block(above: 0.9em, below: 0.3em)[#it] }

#set table(
  stroke: (x, y) => (
    top: if y == 0 { 1pt + azul } else { 0.5pt + cinza.darken(15%) },
    bottom: 0.5pt + cinza.darken(15%),
  ),
  fill: (x, y) => if y == 0 { azul } else if calc.odd(y) { azulclaro } else { white },
  inset: 6pt,
)
#show table.cell.where(y: 0): set text(fill: white, weight: "bold")

#let callout(titulo, corpo, cor: azul) = block(
  width: 100%, fill: cor.lighten(85%), stroke: (left: 3pt + cor),
  inset: 9pt, radius: 3pt, above: 0.9em, below: 0.9em,
)[#text(weight: "bold", fill: cor.darken(10%))[#titulo] \ #corpo]

#let P = text(fill: vermelho, weight: "bold")[CRÍTICA]
#let M = text(fill: amarelo.darken(25%), weight: "bold")[MÉDIA]
#let B = text(fill: verde.darken(25%), weight: "bold")[BAIXA]

// =====================================================================
//  CAPA
// =====================================================================
#align(center)[
  #v(1.2cm)
  #rect(width: 100%, height: 4pt, fill: azul, stroke: none)
  #v(1cm)
  #text(size: 13pt, fill: preto.lighten(20%))[SGA — Sistema de Gestão de Alunos Neurodivergentes]
  #v(0.3cm)
  #text(size: 26pt, weight: "bold")[Plano de Produção]
  #v(0.3cm)
  #text(size: 14pt, fill: preto.lighten(10%))[O que precisa ser implementado e modificado\ para o sistema ficar pronto para produção]
  #v(0.8cm)
  #block(width: 80%)[
    #set text(size: 10pt, fill: preto.lighten(25%))
    Documento de _gap analysis_ baseado na leitura do código atual do repositório
    `sga-neurodivergente` (backend Laravel 11 / PHP 8.3 / PostgreSQL 16 + frontend estático).
    Cobre segurança, portabilidade do ambiente Docker, integração _front_↔API,
    conformidade LGPD e correção de defeitos.
  ]
  #v(0.6cm)
  #rect(width: 100%, height: 4pt, fill: azul, stroke: none)
]

#v(0.8cm)

// =====================================================================
= Sumário executivo
// =====================================================================

A versão atual do sistema foi construída para *demonstração* (defesa do Projeto Integrador), não para operação real. O backend em Laravel é funcional e bem estruturado como MVP acadêmico — tem criptografia de dados sensíveis via `APP_KEY`, trilha de auditoria, _soft delete_, consentimento LGPD e controle de vínculo monitora↔aluno. Porém, três lacunas o impedem de ir para produção:

#callout("As três lacunas estruturais", [
  *1. O frontend não conversa com o backend.* O `frontend/js/script.js` é 100% estático: login conferido no próprio JavaScript, dados _mock_ em `localStorage` e "criptografia AES-256" apenas simulada com `btoa()`. Nenhuma chamada HTTP é feita à API. Hoje são dois sistemas que não se falam.

  *2. Segurança de demonstração.* `APP_DEBUG=true`, senhas de _seeder_ fracas e versionadas (`admin123`), credenciais de banco no `docker-compose.yml`, ausência de _rate limiting_, de CORS e de HTTPS.

  *3. Portabilidade frágil do Docker.* O ambiente só sobe de forma confiável na máquina onde foi montado, por causa de `composer update` em tempo de execução, portas fixas, imagens sem _pin_ e, principalmente, a `APP_KEY` ser regenerada a cada ambiente — o que torna qualquer dado já cifrado ilegível em outra máquina.
], cor: vermelho)

Este documento detalha cada item, com prioridade, local no código e ação recomendada. A seção #ref(<portabilidade>) trata especificamente dos problemas de "rodar em outra máquina" que você observou.

#figure(
  table(
    columns: (auto, 1fr, auto),
    align: (center, left, center),
    [*Área*], [*Situação atual (resumo)*], [*Prioridade*],
    [Portabilidade Docker], [`composer update` em runtime, portas fixas, imagens sem pin, APP_KEY volátil], [#P],
    [Integração front↔API], [Frontend estático; login e criptografia falsos; zero chamadas HTTP], [#P],
    [Segurança / hardening], [APP_DEBUG on, sem rate limit, sem CORS, sem HTTPS, segredos versionados], [#P],
    [Gestão de chave (APP_KEY)], [Chave no `.env`, regenerada por ambiente, sem rotação], [#P],
    [Tratamento de erros], [`withExceptions` vazio; stack trace exposto com debug on], [#M],
    [Defeitos de código], [FK `monitora_id` NULL quando admin registra ocorrência; teste desalinhado], [#M],
    [LGPD — retenção/expurgo], [PDFs de laudo não cifrados e não removidos no _soft delete_], [#M],
    [Paginação / busca], [`GET /alunos` sem paginação; busca inviável em campos cifrados], [#M],
    [Observabilidade / backup], [Sem monitoramento, sem política de _backup_ do banco e dos arquivos], [#M],
    [CI/CD e qualidade], [Sem pipeline; `factories/` vazio; cobertura parcial], [#B],
  ),
  caption: [Visão geral das frentes de trabalho e prioridades.],
)

// =====================================================================
= Portabilidade do ambiente (rodar em outra máquina) <portabilidade>
// =====================================================================

Esta é a causa direta dos problemas que você teve ao rodar fora da sua máquina, mesmo com Docker + Compose. Os pontos abaixo foram confirmados em `backend/docker-compose.yml`, `backend/docker/Dockerfile`, `backend/docker/entrypoint.sh` e `backend/.env.example`.

== `composer update` em vez de `composer install`

Tanto o `Dockerfile` quanto o `entrypoint.sh` executam:
```
composer update --no-interaction --prefer-dist --no-audit 2>/dev/null || true
```
Três problemas de uma vez:
- *`update` ignora o `composer.lock`* → cada máquina (ou cada data) pode resolver versões diferentes das dependências. O build deixa de ser reproduzível.
- *`2>/dev/null || true`* → se a instalação falhar, o erro é engolido e o container "sobe" mesmo assim, sem as dependências. É por isso que em outra máquina pode subir mas não funcionar.
- *O volume `.:/var/www`* (no compose) monta o projeto do host por cima do container, sobrescrevendo o `vendor/` instalado no build. Se o host não tiver `vendor/`, tudo passa a depender do `composer update` do _entrypoint_.

#callout("Ação", [Trocar por `composer install` (respeita o `.lock`), versionar o `composer.lock`, remover `2>/dev/null || true` para que falhas apareçam, e usar um _multi-stage build_ que copie o `vendor/` para dentro da imagem — sem depender do volume do host para dependências. Em produção, montar o código via imagem (não via _bind mount_).], cor: azul)

== APP_KEY regenerada a cada ambiente

O `.env.example` tem `APP_KEY=` vazio e o `entrypoint.sh` roda `php artisan key:generate --force` em todo _boot_ sem chave. Como os dados sensíveis (nome do aluno, ocorrências, laudos, planos de manejo) são cifrados com a `APP_KEY` (via `CriptografiaService` → `Crypt`), *trocar de máquina gera uma chave nova e torna qualquer dado cifrado existente ilegível*. A função `decifrarDados` ainda agrava: ao falhar, ela retorna o próprio texto cifrado em vez de erro, mascarando o problema.

#callout("Ação", [Definir a `APP_KEY` como *segredo gerenciado* (variável de ambiente injetada pelo orquestrador, _secret manager_ ou cofre), igual em todos os ambientes que compartilham o mesmo banco. Nunca regenerar em _boot_. Documentar que a chave é parte do _backup_ (sem ela, não há como ler os dados). Planejar rotação de chave (re-cifrar em migração controlada). Fazer `decifrarDados` falhar de forma explícita em vez de devolver o _ciphertext_.], cor: vermelho)

== Portas fixas e colisão

O compose expõe portas fixas: `8000:80` (web), `5432:5432` (Postgres) e `8080:8080` (Adminer). Em outra máquina que já use o Postgres local na 5432 (muito comum) ou algo na 8000/8080, o `up` falha ou sobe parcialmente.

#callout("Ação", [Parametrizar as portas com variáveis (`${APP_PORT:-8000}`, `${DB_PORT:-5432}`). Em produção, *não* publicar a porta do Postgres para o host (deixar só na rede interna do compose) e remover o Adminer do ambiente produtivo.], cor: azul)

== Imagens sem _pin_ e sem plataforma definida

`php:8.3-fpm-alpine`, `postgres:16-alpine`, `nginx:alpine` e `adminer:latest` usam _tags_ flutuantes, sem _digest_ (`@sha256`). O compose também não fixa `platform:`. Em um Mac ARM (M1/M2/M3), as imagens x86 rodam sob emulação e as extensões PHP compiladas via `pecl`/`$PHPIZE_DEPS` podem divergir. `adminer:latest` pode mudar a qualquer momento.

#callout("Ação", [Fixar _tags_ específicas e, idealmente, _digests_. Definir `platform` ou publicar imagem _multi-arch_. Trocar `adminer:latest` por versão fixa (e removê-lo em produção).], cor: azul)

== Outros pontos de portabilidade

#figure(
  table(
    columns: (1fr, 1.4fr),
    align: (left, left),
    [*Ponto no código*], [*Risco / ação*],
    [`entrypoint.sh`: `until nc -z db 5432` sem _timeout_], [Loop infinito se o banco nunca subir; o container fica preso em "Aguardando Banco". Adicionar limite de tentativas e sair com erro claro.],
    [`entrypoint.sh`: `chmod -R 777 storage bootstrap/cache`], [Permissão insegura e, em volume do Windows, mapeamento de UID divergente. Ajustar _ownership_ correto (usuário do php-fpm) em vez de 777.],
    [`migrate --force` e `db:seed --force` em todo _boot_], [Roda _seed_ com usuários de demonstração automaticamente. Em produção, separar _migrate_ (passo controlado) de _seed_ (nunca em produção).],
    [`#!/bin/bash` em imagem Alpine], [Funciona porque o Dockerfile instala `bash`, mas é frágil. Padronizar para `sh` POSIX ou garantir `bash`.],
    [`DB_HOST=db` fixo], [Correto dentro do compose, mas quebra ao rodar `artisan` fora do container. Documentar / parametrizar.],
    [`.env` não versionado, copiado de `.env.example`], [Sobe com `APP_ENV=local`, `APP_DEBUG=true`, `LOG_LEVEL=debug`. Criar `.env.production` de referência com valores seguros.],
  ),
  caption: [Demais ajustes de portabilidade no ambiente Docker.],
)

// =====================================================================
= Segurança e _hardening_
// =====================================================================

== Configuração de ambiente insegura (padrão)
`APP_ENV=local`, `APP_DEBUG=true` e `LOG_LEVEL=debug` no `.env.example`. Com _debug_ ligado e `withExceptions` vazio em `bootstrap/app.php`, qualquer exceção não tratada retorna _stack trace_ completo ao cliente.
#callout("Ação", [Em produção: `APP_ENV=production`, `APP_DEBUG=false`, `LOG_LEVEL=warning`. Implementar _handler_ de exceções que retorne JSON padronizado (sem vazar _trace_).], cor: azul)

== Segredos versionados
`docker-compose.yml` e `.env.example` trazem `POSTGRES_PASSWORD=sgan_secret` em texto. Senha fraca e no repositório.
#callout("Ação", [Mover credenciais para variáveis de ambiente / _secret manager_. Gerar senha forte por ambiente. Garantir que `.env` real nunca seja versionado (já está no `.gitignore`).], cor: vermelho)

== _Seeders_ com senhas fracas
`UserSeeder` cria `admin@escola.edu.br / admin123`, `monitora@escola.edu.br / monitora123` (e variações `@instituicao.edu.br`), executados automaticamente pelo _entrypoint_.
#callout("Ação", [Não rodar _seed_ de usuários em produção. Criar o primeiro administrador por comando interativo (`artisan`) com senha forte, ou por processo de _bootstrap_ controlado.], cor: azul)

== Ausência de _rate limiting_
Nenhuma rota tem `throttle` — nem o `POST /api/login`. Isso deixa o login exposto a força bruta.
#callout("Ação", [Aplicar `throttle` no login (ex.: por IP + login) e um limite geral nas rotas autenticadas. Considerar bloqueio progressivo após N tentativas.], cor: vermelho)

== CORS e HTTPS
Não há `config/cors.php` no projeto (o front atual nem chama a API, então nunca foi necessário). O nginx (`docker/nginx/default.conf`) serve HTTP puro, sem TLS nem cabeçalhos de segurança.
#callout("Ação", [Ao integrar o front: configurar CORS restrito à origem do front. Em produção: TLS (certificado via proxy/_load balancer_ ou Let's Encrypt) e cabeçalhos de segurança (HSTS, X-Content-Type-Options, etc.).], cor: azul)

// =====================================================================
= Integração Frontend ↔ API
// =====================================================================

Hoje o `frontend/` e o `backend/` são *dois sistemas independentes*. O `frontend/js/script.js` não faz nenhuma requisição HTTP: o login é comparado literalmente em JavaScript, os dados vêm de constantes _mock_ (`SEED_ALUNOS`, `SEED_OCORRENCIAS`) persistidas em `localStorage`, e a "criptografia AES-256" é encenada — gera um texto com `btoa(...)` e guarda os fatos em claro dentro de `(Fatos: ...)`, que outra função extrai de volta.

#callout("Ação — reescrever a camada de dados do front", [
  - Substituir o login _hardcoded_ por `POST /api/login`, guardando o _token_ Sanctum e enviando-o como `Authorization: Bearer` nas demais chamadas.
  - Trocar todo o acesso a `localStorage`/_mock_ por chamadas reais aos _endpoints_ (`/api/alunos`, `/api/ocorrencias`, `/api/laudos/...`, etc.).
  - Remover a criptografia simulada — a cifra real já acontece no servidor.
  - Remover atalhos de demonstração (`preencherCredenciais`, `simularPerfil`) e mensagens de banca.
  - Tratar _token_ expirado/401 (redirecionar ao login) e erros de rede.
], cor: vermelho)

// =====================================================================
= Conformidade LGPD — pendências
// =====================================================================

A base de LGPD está montada (cifra, auditoria, consentimento, _soft delete_, portabilidade e vínculo monitora↔aluno), mas restam lacunas:

#figure(
  table(
    columns: (1fr, 1.3fr, auto),
    align: (left, left, center),
    [*Pendência*], [*Ação*], [*Prior.*],
    [PDFs de laudo não cifrados em disco], [Cifrar o arquivo em repouso ou usar _storage_ com criptografia; manter fora de disco público (já está em `laudos_privados`).], [#M],
    [Laudos órfãos após _soft delete_], [Rotina de expurgo que remove arquivos quando o aluno é apagado/retido; política de retenção documentada.], [#M],
    [`exportar-lgpd` sem checagem de vínculo], [`AlunoController::exportarLgpd` deve validar que a monitora tem vínculo com o aluno (hoje qualquer autenticado exporta qualquer aluno).], [#P],
    [Exportação só em JSON], [Oferecer também PDF, conforme previsto na documentação do projeto (direito à portabilidade).], [#B],
    [_Logs_ de auditoria incompletos], [Registrar também login/logout e acesso à lista `GET /alunos`.], [#B],
    [CPF/e-mail de responsável em claro], [Avaliar cifrar `responsaveis.cpf`/`email` como os demais dados pessoais.], [#M],
  ),
  caption: [Pendências de conformidade e proteção de dados.],
)

// =====================================================================
= Defeitos de código a corrigir
// =====================================================================

== FK `monitora_id` nula ao registrar ocorrência como admin
A migration de `ocorrencias` define `monitora_id` como *NOT NULL* (`constrained`), mas `OcorrenciaController::store` grava `monitora_id => $monitora ? $monitora->id : null` quando quem registra é um admin sem registro de `Monitora`. No PostgreSQL isso viola a restrição → erro 500 no fluxo "admin registra ocorrência".
#callout("Ação", [Decidir a regra de negócio: ou tornar `monitora_id` _nullable_ (ocorrência pode não ter monitora), ou impedir que admin registre ocorrência, ou associar a ocorrência a uma monitora válida. Ajustar _migration_ + _controller_ + teste de forma coerente.], cor: amarelo)

== Teste desalinhado do schema
`LgpdAdequacaoTest::setUp` cria `Administrador` com `cargo` e `cpf`, campos que não existem no model (`fillable: [user_id, setor, nivel_acesso]`) nem na tabela `administradores`.
#callout("Ação", [Alinhar o teste ao schema real (ou adicionar as colunas, se forem desejadas). Criar _factories_ (hoje `database/factories/` só tem `.gitkeep`) para padronizar a montagem de dados nos testes.], cor: amarelo)

== `nivel_acesso` definido mas nunca usado
`administradores.nivel_acesso` é editado em `PermissaoController` mas não é verificado em nenhuma rota — a autorização real é só por `perfil` (string). Ou se implementa a verificação, ou se remove o campo para não dar falsa sensação de controle.

// =====================================================================
= Escalabilidade e operação
// =====================================================================

#figure(
  table(
    columns: (1fr, 1.4fr),
    align: (left, left),
    [*Tema*], [*Ação*],
    [Paginação], [`GET /alunos` retorna todos os registros e decifra em memória. Adicionar paginação em alunos, ocorrências e auditoria.],
    [Busca em campos cifrados], [Como a cifra usa IV aleatório, não há `WHERE LIKE` nos campos sensíveis. Para buscar por nome, avaliar _hash_ determinístico (_blind index_) ou campo de busca normalizado, com os devidos cuidados de LGPD.],
    [Tratamento de erros], [Preencher `withExceptions` com _handler_ que padroniza respostas de erro e esconde detalhes internos.],
    [Observabilidade], [Centralizar _logs_, adicionar métricas e _health check_ real (hoje só há `/up`).],
    [Backup e restauração], [Rotina de _backup_ do PostgreSQL *e* do diretório de laudos *e* da APP_KEY; testar restauração. Sem a chave, o _backup_ do banco é inútil.],
    [Fila/jobs], [`QUEUE_CONNECTION=database` configurado, mas sem _worker_. Se usar filas (ex.: expurgo, e-mails), subir um processo de _worker_.],
  ),
  caption: [Itens de escalabilidade e operação contínua.],
)

// =====================================================================
= Roteiro sugerido
// =====================================================================

#figure(
  table(
    columns: (auto, 1.5fr, auto),
    align: (center, left, center),
    [*Fase*], [*Escopo*], [*Prioridade*],
    [1], [Tornar o ambiente reproduzível: `composer install` + `.lock`, _pin_ de imagens, portas parametrizadas, APP_KEY como segredo fixo, _timeout_ no _wait_ do banco.], [#P],
    [2], [_Hardening_: `APP_DEBUG=false`, _rate limiting_ no login, segredos fora do repositório, _handler_ de exceções, primeiro admin via comando.], [#P],
    [3], [Integração real do frontend com a API (login Sanctum, chamadas HTTP, remoção de _mock_ e cripto falsa), CORS + HTTPS.], [#P],
    [4], [Correção de defeitos (FK de ocorrência, teste/factories), pendências LGPD (vínculo em export, expurgo de laudos).], [#M],
    [5], [Operação: paginação/busca, _backup_/restauração, observabilidade, CI/CD.], [#M],
  ),
  caption: [Ordem recomendada de execução, da base para o topo.],
)

#v(0.5cm)
#callout("Observação final", [O backend tem uma base sólida — o esforço maior não é reescrever, e sim (a) estabilizar o ambiente para rodar igual em qualquer máquina, (b) ligar o frontend à API de verdade e (c) desligar os atalhos de demonstração. Feitas as fases 1 a 3, o sistema já se torna um produto real; as fases 4 e 5 o deixam robusto para uso contínuo.], cor: verde.darken(20%))
