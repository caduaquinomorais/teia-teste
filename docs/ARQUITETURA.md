# Arquitetura

## Visão geral

```
Navegador ──► Apache (.htaccess) ──► public/index.php (front controller)
                                        │
                                        ├─ Request::fromGlobals()   base path automático (ex.: /teia)
                                        ├─ App::handle()            roteador, SiteController, tratamento de erros
                                        │     └─ App::content()     lê app/content/<nome>.php
                                        └─ SecurityHeaders::apply   CSP com nonce, nosniff, HSTS (HTTPS)…
```

O site não tem banco de dados, sessões, cookies, formulários, uploads nem diretório de dados. Todo o estado está no repositório Git.

## Estrutura do repositório

```
public/            pasta pública: index.php, .htaccess, assets (css, js, fontes, ícones, imagens, docs)
app/
  bootstrap.php    autoloader PSR-4 (Teia\ → app/src) e helpers
  src/
    Core/          App (rotas, conteúdo, erros), Config, View, Logger, helpers (e(), url(), asset(), csp_nonce())
    Http/          Request, Response, Router, HttpException, Controller/SiteController
    Security/      SecurityHeaders, UrlValidator
    View/          Render (picture, links, parágrafos, PDFs)
  content/         CONTEÚDO do site: teia.php, layout.php, erro-404.php
  views/           templates PHP: site/ (layout, cabeçalho, rodapé, página, 404) e error.php
  config/          app.php (padrões) + app.local.php opcional (fora do Git)
bin/               php.sh, serve.sh, php-dev.ini, preparar-assets.php (desenvolvimento)
tests/             run.php, unit/, http/, visual/, lib/
docs/
```

## Ciclo de uma requisição

1. O `.htaccess` da raiz encaminha tudo para `public/`. O `.htaccess` de `public/` manda para `index.php` o que não é arquivo estático.
2. `Request::fromGlobals()` calcula o **base path** pelo `SCRIPT_NAME`, o que permite rodar em subpasta (`/teia`).
3. `App::handle()` gera o **nonce** da CSP e responde:
   - `GET /` → `SiteController::home`;
   - qualquer outro caminho → **404** (página do site);
   - outro método → **405**.
4. Erros inesperados viram uma página **500 genérica com código de referência**. Os detalhes vão só para o log do PHP do servidor (`error_log`).
5. Toda resposta recebe os cabeçalhos de segurança.

## Conteúdo

- `App::content('teia')` lê `app/content/teia.php`, um array PHP com os textos do Figma.
- Os templates em `app/views/site/` fixam o layout e a ordem das seções. `View\Render` escapa toda a saída.
- A variável de ambiente `TEIA_CONTENT_DIR` troca a pasta de conteúdo. Ela é usada **só** pelo teste visual, que simula o estado do Figma (dúvida respondida e PDFs).

## Configuração

`app/config/app.php` define os padrões. Para sobrescrever num servidor, crie `app/config/app.local.php`, que fica fora do Git:

| Chave | Uso |
|---|---|
| `base_path` | Fixa o caminho base (ex.: `'/teia'`) se a detecção automática falhar |
| `force_https` | Envia HSTS mesmo atrás de proxy que não informa HTTPS |
| `trusted_proxies` | IPs de proxy cujo `X-Forwarded-Proto` é aceito |

## Decisões técnicas

| Decisão | Motivo |
|---|---|
| Conteúdo em arquivos PHP | Pedido do responsável: sem CMS. Um array PHP não precisa de parser nem de banco, e o PHP valida a sintaxe (`php -l`). |
| Nenhuma biblioteca externa | Exigência do escopo. |
| Sem `php_value` no `.htaccess` | Na KingHost (PHP-FPM) essa diretiva causa erro 500. |
| Proteção contra execução direta | Todo arquivo PHP em `app/` começa com `defined('TEIA_ROOT') || exit;`. Isso protege mesmo se o servidor ignorar o `.htaccess` (Nginx). |
