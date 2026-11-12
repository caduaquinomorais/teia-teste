# Testes

## Como executar

```bash
bin/php.sh tests/run.php            # tudo (unitários + HTTP)
bin/php.sh tests/run.php unit       # só unitários
bin/php.sh tests/run.php http       # só HTTP
tests/visual/executar.sh            # visual + responsividade + acessibilidade (Node 22+ e Chrome/Edge)
```

- **Runner próprio** (`tests/run.php`), sem PHPUnit. Cada arquivo retorna `['nome' => fn () => ...]`.
- **HTTP:** os testes sobem o **servidor embutido do PHP** em porta livre. Alguns usam uma pasta de conteúdo temporária (`TEIA_CONTENT_DIR`), por exemplo para simular um erro ou um texto com HTML.
- **Visual:**
  1. `tests/visual/preparar.php` cria uma pasta de conteúdo temporária que reproduz o estado do frame do Figma: a 1ª dúvida respondida com o texto de layout do Figma e links de PDF de teste. Esse conteúdo nunca é publicado.
  2. `tests/visual/comparar.mjs` controla o Chrome headless via DevTools Protocol, usando o WebSocket nativo do Node, sem npm.
  3. Ele gera `tests/visual/reports/relatorio.html`, fora do Git.

## Últimos resultados — 12/11/2026 (versão sem CMS)

**Ambiente:** Windows 11 Pro 10.0.26200 · PHP 8.3.36 (NTS) · Node 26.13.1 · Google Chrome headless (`--headless=new`).

| Bateria | Resultado |
|---|---|
| Unitários (`tests/unit`) | **13/13** |
| HTTP (`tests/http`) | **12/12** |
| **Total PHP** | **25/25** |
| Visual, responsividade e acessibilidade | **105/105 verificações** |

### O que os testes cobrem

| Área | Testes |
|---|---|
| Roteamento | Parâmetros com regex, caminho com `../`, 404, 405 com `Allow`, HEAD, base path em subpasta |
| Conteúdo | Textos exatos do Figma; nenhum Lorem ipsum; nome de arquivo de conteúdo com caminho é recusado |
| XSS | `e()`; texto com `<script>` no arquivo de conteúdo aparece escapado no site; links `javascript:` e outros esquemas perigosos ficam sem `href` |
| Assets | Imagem com WebP + fallback; nome de imagem ou PDF com caminho é ignorado; PDF só se existir ou for URL `https` |
| Cabeçalhos | CSP com nonce que muda a cada requisição, `frame-src` só na home, nosniff, Referrer-Policy, Permissions-Policy, frame-ancestors; sem X-Powered-By; sem HSTS em HTTP |
| Superfície | Nenhum cookie; `/admin`, `/instalar`, `/cron/backup` e `/midia/…` respondem 404; POST responde 405; código-fonte e conteúdo não são servidos |
| Erros | 500 com código de referência e sem detalhes internos; 404 personalizada |
| Conteúdo pendente | Perguntas sem resposta e documentos sem PDF não exibem nada provisório |

### Comparação visual com o Figma (1440 px)

| Verificação | Tolerância | Resultado |
|---|---|---|
| Altura das 11 seções | ±4 px | 11/11 |
| Cor de fundo das seções | exata | 11/11 |
| Posição e tamanho de títulos, botões, fotos, mapa, itens do FAQ e cards (24 elementos) | ±2 px | 24/24 |
| Fonte (Geist local) e peso | exato | 9/9 |

A diferença de pixels por seção fica entre 2% e 7%. Ela vem da suavização de fontes (Figma × Chrome), do mapa (imagem no Figma × Google Maps real) e de uma quebra de linha no texto de teste.

### Responsividade e acessibilidade

Nas larguras 320, 360, 390, 414, 768, 1024, 1280 e 1440 px:

- sem rolagem horizontal;
- fonte mínima de 12 px;
- alvos de toque de 44 px abaixo de 1024 px;
- menu mobile abre e fecha com Esc;
- contraste WCAG AA em todos os textos;
- `lang`, `alt`, rótulos, título dos iframes, link "Pular para o conteúdo" como 1º Tab, foco visível e um único `h1`.

## O que NÃO foi testado

| Item | Motivo |
|---|---|
| Servidor real da KingHost (Apache/PHP-FPM, `.htaccess`, Git Webhook) | Sem acesso ao servidor. Ver checklist em DEPLOY.md. |
| Safari e Firefox | Só o Chrome headless estava disponível. |
| Leitores de tela reais | Foram feitas verificações automáticas de estrutura, rótulos e foco. |
| PHP 8.1 e 8.2 | Os testes rodaram em 8.3. O código evita recursos posteriores à 8.1. |
