# Segurança

Referência: OWASP ASVS nível 2. Cada controle abaixo tem um teste que tenta burlá-lo. A coluna "Teste" indica o arquivo em `tests/`.

## Autenticação e senhas

| Controle | Implementação | Teste |
|---|---|---|
| Hash de senha | Argon2id (`PasswordPolicy::hash`), com bcrypt custo 12 se o servidor não tiver Argon2id; rehash automático no login | unit/Seguranca |
| Política | Mínimo de 12 e máximo de 128 caracteres; lista de senhas comuns (`app/config/common-passwords.txt`) e variações com sufixo numérico; proíbe conter o usuário; exige variedade mínima de caracteres | unit/Seguranca, http/Instalador |
| Mensagens genéricas | "Usuário ou senha inválidos." para usuário inexistente, senha errada ou conta desativada; hash fictício para igualar o tempo de resposta | http/Autenticacao |
| Força bruta | Limite por usuário (5 em 15 min) e por IP (20 em 15 min); chaves HMAC; IP nunca gravado em texto | unit/Seguranca, http/Autenticacao |
| Sem senha padrão | A primeira conta só é criada com o token do instalador | http/Instalador |

## Sessões

| Controle | Implementação |
|---|---|
| Cookie `teia_admin` | `HttpOnly`, `SameSite=Lax`, `Secure` em HTTPS, `Path=<base>/admin` |
| Visitantes | A sessão só é iniciada em rotas do painel: o site público não emite cookies |
| Fixação | `session.use_strict_mode=1` e `session_regenerate_id(true)` no login e na troca de senha |
| Expiração | 30 min de inatividade e 8 h no máximo (configuráveis) |
| Armazenamento | `<dados>/sessions`, fora da área pública |
| Revogação | Versão de sessão por usuário (ver PERMISSOES.md) |

## CSRF

- **Painel:** token sincronizador na sessão (`_csrf`), conferido com `hash_equals` em **todo** POST, além da validação de `Origin` e de `Referer`.
- **Instalador e recuperação (sem sessão):** token HMAC com carimbo de tempo e finalidade, válido por 2 h.
- **Formulários públicos:** não existem, porque o TEIA não tem formulário.

## Cabeçalhos

| Cabeçalho | Valor |
|---|---|
| Content-Security-Policy | `default-src 'self'`; `script-src`/`style-src` com **nonce por requisição**; `img-src 'self' data:`; `frame-src` só `https://www.google.com` e `https://maps.google.com`, e somente na página que tem o mapa; `object-src 'none'`; `base-uri 'self'`; `form-action 'self'`; `frame-ancestors 'none'` |
| X-Content-Type-Options | `nosniff` |
| Referrer-Policy | `strict-origin-when-cross-origin` |
| Permissions-Policy | câmera, microfone, geolocalização, pagamento e USB desativados |
| X-Frame-Options | `DENY` |
| Strict-Transport-Security | `max-age=31536000`, só em HTTPS, **sem** `includeSubDomains`, porque o domínio é compartilhado com outros projetos |
| X-Powered-By | removido |
| Painel | `Cache-Control: no-store` e `X-Robots-Tag: noindex` |

## Saída e conteúdo

- `e()` = `htmlspecialchars` com `ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE`, usado em todos os templates.
- **Texto rico:** escapa tudo antes de converter `**negrito**`, `*itálico*`, `[link](url)` e quebras de linha. HTML nunca passa.
- **URLs** (`UrlValidator`): aceita só `http`, `https`, `mailto`, `tel` e `#âncora`. Recusa `javascript:`, `data:`, `//host`, credenciais na URL e caracteres de controle. O mapa personalizado aceita só `https://www.google.com/maps…`.
- **CMS:** validação no servidor com **lista branca** de campos; chaves desconhecidas são descartadas.

## Uploads

| Controle | Imagens | PDF |
|---|---|---|
| Tamanho máximo | 8 MB | 15 MB |
| Tipo | `finfo` **e** `getimagesize` devem concordar (JPEG, PNG ou WebP) | `finfo = application/pdf` e assinatura `%PDF-` |
| Conteúdo | **Reprocessadas com GD**: metadados (EXIF/GPS) e cargas escondidas são descartados; limite de pixels contra "decompression bomb" | Recusa `/JavaScript`, `/JS`, `/Launch`, `/EmbeddedFile`, `/RichMedia`, `/XFA`, `/SubmitForm` e `/ImportData` (heurística conservadora) |
| SVG | **proibido** | — |
| Nome | aleatório (128 bits) | aleatório |
| Local | `<dados>/uploads`, fora de `public/` | idem |
| Entrega | rota `/midia/…` com regex estrita, `nosniff` e `CSP: default-src 'none'; sandbox` | idem, mais `Content-Disposition: attachment` |

## Erros e logs

- O visitante vê só uma mensagem genérica e um **código de referência**.
- Stack trace, caminho, mensagem e rota vão só para `<dados>/logs/app-AAAA-MM-DD.log`.
- `display_errors=0`; avisos do PHP viram exceções e são registrados.

## Segredos

| Segredo | Onde fica |
|---|---|
| `app.key` (32 bytes aleatórios) | Diretório de dados, fora do Git, permissão 0600. Subchaves por finalidade (HMAC-SHA256) para CSRF, rate limit, auditoria e cron. |
| Código do Cronjob | Só o HMAC é gravado no banco. |
| Credenciais SMTP | **Não existem**: sem formulários não há envio de e-mail. A criptografia de segredos com libsodium não foi implementada por não ter uso. |

## Proteção do código-fonte no servidor

- O `.htaccess` da raiz encaminha tudo para `public/` e responde 404 para `app/`, `bin/`, `tests/`, `docs/` e arquivos ocultos.
- Há `.htaccess` com `Require all denied` em `app/`, `bin/`, `tests/`, `docs/` e no diretório de dados.
- Todo arquivo PHP em `app/` começa com `defined('TEIA_ROOT') || exit;`. Ele não executa nada se for chamado diretamente, o que protege caso o servidor seja Nginx e ignore o `.htaccess`.

## Instalação e recuperação

- `/instalar` recusa o diretório de dados dentro do document root.
- O token de 128 bits fica num arquivo do diretório de dados, lido via FTP.
- Tentativas limitadas: 5 por IP e 20 no total em 15 min.
- Depois de instalado, `/instalar` responde 404 para sempre.
- `/admin/recuperar`: token de uso único, válido por 30 min, com as mesmas limitações; só redefine contas de **administrador**.

## Backups

Restauração protegida contra:

- **Zip Slip:** só nomes de entrada exatos são aceitos.
- **Adulteração:** SHA-256 de cada arquivo, conferido com o manifesto.
- **Versão de esquema mais nova** que o código.
- **Banco corrompido:** `PRAGMA integrity_check`.
- **Backup sem administrador ativo.**

Um backup de segurança é criado antes de restaurar.

## Revisão de segurança (F5) — 12/11/2026

Revisão manual do código e testes adversariais (`tests/run.php`: 110/110). **Nenhum achado crítico ou alto em aberto.**

Itens de risco médio ou baixo, aceitos e documentados:

| Item | Risco | Situação |
|---|---|---|
| HTTPS atrás de proxy (Varnish da KingHost) | Médio | Se o servidor não informar HTTPS ao PHP, os cookies podem sair sem `Secure` e o HSTS não é enviado. **Ação no deploy:** definir `'force_https' => true` em `config.local.php` e ativar o redirecionamento HTTPS no `.htaccess` (DEPLOY.md). |
| Mapa do Google (iframe de terceiros) | Baixo | O Google pode definir cookies dentro do iframe. Liberado só em `frame-src`, na página do mapa. Avaliar se a política de privacidade da ADE SAMPA cobre isso. |
| Heurística de PDF | Baixo | Os PDFs não são reescritos; a proteção é a recusa de objetos ativos e o download forçado. |
| Arquivos órfãos em `uploads/` | Baixo | Imagens substituídas não são apagadas automaticamente, porque o histórico ainda as referencia. Ver MANUTENCAO.md. |
| POST maior que `post_max_size` | Baixo | O PHP descarta o corpo e o CSRF falha (403 "Sessão expirada"). É seguro, mas a mensagem confunde. Os limites ficam documentados na tela de backups. |
