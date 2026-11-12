# Arquitetura

## Visão geral

```
Navegador ──► Apache (.htaccess) ──► public/index.php (front controller)
                                        │
                                        ├─ Request::fromGlobals()   base path automático (ex.: /teia)
                                        ├─ App::handle()            roteador, controladores, tratamento de erros
                                        │     ├─ SiteController     site público (sem sessão, sem cookies)
                                        │     ├─ Admin*Controller   painel (sessão, CSRF, permissões)
                                        │     └─ InstallController  /instalar (somente antes da instalação)
                                        └─ SecurityHeaders::apply   CSP com nonce, nosniff, HSTS (HTTPS)…

Dados persistentes (fora do Git e da área pública):
../teia-ade-sampa-data/
├── database/teia.sqlite   conteúdo, usuários, histórico, auditoria
├── uploads/               imagens reprocessadas e PDFs (servidos por /midia/…)
├── backups/               ZIPs de backup
├── logs/                  app-AAAA-MM-DD.log
├── sessions/              sessões do painel
├── tmp/
├── app.key                chave mestra (32 bytes)
└── config.local.php       configuração local (opcional)
```

## Estrutura do repositório

```
public/            única pasta que deveria ser pública: index.php, .htaccess, assets (css, js, fontes, ícones, imagens)
app/
  bootstrap.php    autoloader PSR-4 (Teia\ → app/src) e helpers
  src/
    Core/          App (serviços e rotas), Paths, Config, View, Logger, helpers (e(), url(), asset())
    Http/          Request, Response, Router, HttpException, Controller/*
    Database/      Database (PDO + transações), Migrator
    Security/      Keys, Csrf, SecurityHeaders, PasswordPolicy, RateLimiter, UrlValidator, RichText
    Auth/          Auth, SessionManager, UserRepository, Audit
    Cms/           PageRegistry, Schema (tipos/validação), ContentRepository, MediaService, Render
    Backup/        BackupService
    Install/       Installer
  content/         DEFINIÇÕES das páginas editáveis (teia.php, layout.php, erro-404.php)
  views/           templates PHP (site/, admin/, install/, error.php)
  migrations/      001_estrutura_inicial.sql …
  config/          app.php (padrões), common-passwords.txt
  vendor/          vazio: nenhuma biblioteca de terceiros
bin/               php.sh, serve.sh, php-dev.ini, preparar-assets.php (desenvolvimento)
tests/             run.php, unit/, http/, visual/, lib/
docs/
```

Os módulos `Mail/` e `Contact/` previstos no escopo original **não foram criados**: o Figma do TEIA não tem formulário, e a ausência foi confirmada pelo responsável em 12/11/2026 (ver `PENDENCIAS_DE_CONTEUDO.md`, P-10).

## Ciclo de uma requisição

1. O `.htaccess` (da raiz do repositório e de `public/`) manda para `public/index.php` tudo o que não é arquivo estático.
2. `Request::fromGlobals()` calcula o **base path** a partir do `SCRIPT_NAME`, o que permite rodar em subpasta (`/teia`).
3. `App::handle()` gera o **nonce** da CSP, registra as rotas e chama o controlador.
4. Exceções:
   - `HttpException` vira uma resposta com o status indicado.
   - Qualquer outro erro vira **500 genérico com código de referência**; os detalhes ficam só em `logs/`.
5. Toda resposta recebe os cabeçalhos de segurança.

## Rotas

| Método | Caminho | Quem |
|---|---|---|
| GET | `/` | site público |
| GET | `/midia/{32hex}.{webp,jpg,png,pdf}` | arquivos enviados pelo painel |
| GET/POST | `/instalar` | somente antes da instalação (depois: 404) |
| GET | `/cron/backup` | Cronjob da KingHost (cabeçalho `X-Cron-Auth`) |
| GET/POST | `/admin/login`, POST `/admin/sair` | todos |
| GET/POST | `/admin/recuperar` | recuperação por token |
| GET | `/admin` | usuários autenticados |
| GET/POST | `/admin/senha` | usuários autenticados |
| GET/POST | `/admin/paginas/{slug}`, `/historico`, POST `/restaurar/{versão}` | administradores ou editores com permissão na página |
| GET/POST | `/admin/usuarios…`, `/admin/backups…`, GET `/admin/auditoria` | somente administradores |

## CMS: definição no código, conteúdo no banco

- Cada arquivo em `app/content/` descreve as seções e os campos de uma página. Os **valores padrão** são os textos do Figma.
- O formulário do painel é **gerado automaticamente** a partir dessa definição (`views/admin/_field.php`).
- `Schema::normalize()` valida no servidor e só aceita chaves declaradas (lista branca).
- `ContentRepository` grava o JSON publicado em `pages` e uma cópia em `page_revisions`.
- `Schema::resolve()` mescla os padrões com os valores do banco. Por isso um **campo novo não exige migração**: ele usa o padrão até ser editado.
- Os templates (`views/site/*.php`) fixam o layout, a ordem das seções e as cores. O CMS só altera o conteúdo.

## Decisões técnicas

| Decisão | Motivo |
|---|---|
| SQLite com `journal_mode=DELETE` (sem WAL) | WAL cria arquivos `-wal`/`-shm` e é menos previsível em hospedagem compartilhada. |
| `BEGIN IMMEDIATE` e SAVEPOINT para aninhamento | Evita deadlocks de upgrade de trava; o controle de profundidade é próprio. |
| Diretório de dados resolvido de forma determinística | Variável `TEIA_DATA_DIR`, depois `app/config/data-path.local.php`, depois a pasta irmã do projeto. Se a pasta irmã ficar dentro do document root (repositório em `www/teia`), sobe para o pai do document root. |
| Sessão iniciada só em `/admin` | Visitantes não recebem cookies. |
| Nenhuma biblioteca externa | Exigência do escopo; `app/vendor/` está vazio. |
| `php_value` fora do `.htaccess` | Na KingHost (PHP-FPM) essas diretivas causam erro 500; os ajustes são feitos via `ini_set()` no código. |
