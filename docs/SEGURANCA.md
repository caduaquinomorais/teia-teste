# Segurança

Com a remoção do CMS, o site não tem login, sessões, cookies, formulários, uploads nem banco de dados. A superfície de ataque se resume a servir páginas estáticas geradas em PHP. Os controles abaixo cobrem o que resta. Cada um tem teste em `tests/`.

| Controle | Implementação | Teste |
|---|---|---|
| Escape de saída | `e()` = `htmlspecialchars` com `ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE` em todos os templates. Textos com HTML são exibidos como texto. | unit/Nucleo, http/Site ("sem XSS") |
| Links | `UrlValidator` aceita só `http`, `https`, `mailto`, `tel` e `#âncora`; recusa `javascript:`, `data:`, `//host`, credenciais e caracteres de controle. Link inválido ou vazio fica sem `href`. | unit/Nucleo |
| Mapa | O iframe só é liberado na CSP (`frame-src`) da página inicial, para `www.google.com` e `maps.google.com`. O mapa personalizado só é aceito se for `https://www.google.com/maps…`. | http/Site |
| PDFs | Só nomes simples de arquivo em `public/assets/docs` ou URLs `https` válidas; caminhos com `..` são ignorados. | unit/Nucleo |
| Imagens | Só nomes simples (`a-z0-9-`) de arquivos em `public/assets/images`. | unit/Nucleo |
| Conteúdo | `App::content()` só aceita nomes `a-z0-9-` (sem caminho). | unit/Nucleo |
| Cookies | O site não define nenhum cookie. | http/Site |
| Erros | Página genérica com código de referência; detalhes só no log do PHP; `display_errors=0`. | http/Site |
| Código-fonte | O `.htaccess` da raiz responde 404 para `app/`, `bin/`, `tests/`, `docs/` e arquivos ocultos; há `Require all denied` nessas pastas; todo PHP de `app/` encerra se for chamado diretamente. | http/Site |

## Cabeçalhos

| Cabeçalho | Valor |
|---|---|
| Content-Security-Policy | `default-src 'self'`; `script-src`/`style-src` com **nonce por requisição**; `img-src 'self' data:`; `frame-src` Google Maps só na home (`'none'` nas demais páginas); `object-src 'none'`; `base-uri 'self'`; `form-action 'self'`; `frame-ancestors 'none'` |
| X-Content-Type-Options | `nosniff` |
| Referrer-Policy | `strict-origin-when-cross-origin` |
| Permissions-Policy | câmera, microfone, geolocalização, pagamento e USB desativados |
| X-Frame-Options | `DENY` |
| Strict-Transport-Security | `max-age=31536000`, só em HTTPS, **sem** `includeSubDomains` (domínio compartilhado com outros projetos) |
| X-Powered-By | removido |

## Riscos residuais

| Item | Risco | Observação |
|---|---|---|
| HTTPS atrás do Varnish da KingHost | Baixo | Se o servidor não informar HTTPS ao PHP, o HSTS não é enviado. Ative o redirecionamento HTTPS no `.htaccess` e, se necessário, `force_https` (DEPLOY.md). Como não há cookies, não existe risco de sessão. |
| Google Maps (iframe de terceiros) | Baixo | O Google pode definir cookies dentro do iframe. Avaliar se a política de privacidade da ADE SAMPA cobre isso. |
| Conteúdo editado por quem tem acesso ao Git | — | O conteúdo é código. Quem pode fazer push pode alterar o site: proteja o repositório (privado, com acesso restrito e revisão de pull requests). |
