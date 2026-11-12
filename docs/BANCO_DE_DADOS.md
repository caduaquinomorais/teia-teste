# Banco de dados

SQLite, acessado via PDO (`app/src/Database/Database.php`). O arquivo fica em `<dados>/database/teia.sqlite`.

## Configuração da conexão

- `PRAGMA foreign_keys = ON`
- `PRAGMA busy_timeout = 5000`, que espera até 5 s por uma trava em vez de falhar na hora
- `PRAGMA journal_mode = DELETE` e `synchronous = FULL`, opções conservadoras para hospedagem compartilhada
- Erros como exceção, `FETCH_ASSOC` e prepared statements nativos

## Transações

`Database::transaction(callable)`:

- O nível externo usa `BEGIN IMMEDIATE`, que reserva a escrita logo no início e evita *deadlock* de upgrade de trava.
- Níveis internos viram `SAVEPOINT sp_N`. Se der erro, o código faz `ROLLBACK TO` só daquele nível.
- O controle de profundidade é próprio (`transactionDepth()`).

## Migrações

- Ficam em `app/migrations/NNN_descricao.sql`, numeradas.
- A versão atual do banco fica em `PRAGMA user_version`.
- Migrações pendentes rodam **automaticamente** na primeira requisição depois do deploy de um código novo. Antes de alterar a estrutura de um banco existente, o sistema cria um backup do tipo `pre-migracao`.
- Cada migração roda numa transação e já grava o novo `user_version`.
- Se o banco for de uma versão mais nova que o código, a aplicação para com erro em vez de arriscar corromper dados.

**Para criar uma migração:** adicione `002_minha_mudanca.sql`. Não edite migrações que já foram publicadas.

**Campos novos do CMS não precisam de migração.** O conteúdo é gravado em JSON e os padrões vêm da definição no código.

## Tabelas (migração 001)

| Tabela | Conteúdo |
|---|---|
| `settings` | Chave/valor: `installed_at`, `last_backup_at`, `last_auto_backup_at`, `backup_retention`, `cron_secret_hash` |
| `users` | Contas: usuário único (sem diferenciar maiúsculas), nome, e-mail, hash da senha, papel (`admin`/`editor`), ativo, troca obrigatória de senha, `session_version`, datas |
| `page_permissions` | (usuário, página): sem linha, sem acesso |
| `pages` | Conteúdo publicado por página (JSON) e `version` para o controle otimista |
| `page_revisions` | Histórico completo (página, versão, JSON, autor, observação), único por (página, versão) |
| `media` | Arquivos enviados: id aleatório, tipo (`image`/`pdf`), nome original, MIME, tamanho, dimensões, variantes (`webp`, `fallback`, `file`) |
| `rate_limits` | Baldes HMAC: contagem e início da janela |
| `audit_log` | Auditoria: usuário, ação, alvo, detalhes JSON, HMAC do IP, data |

## Controle otimista de concorrência

O formulário de edição envia a `version` que foi carregada. No `save()`, dentro de `BEGIN IMMEDIATE`:

- Se a versão atual for diferente da enviada, o sistema lança `ConflictException`, responde **HTTP 409** e não grava nada.
- Se não, grava `version + 1` em `pages` e cria a revisão correspondente.
