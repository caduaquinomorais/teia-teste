# Testes

## Como executar

```bash
bin/php.sh tests/run.php            # tudo (unitários + HTTP)
bin/php.sh tests/run.php unit       # só unitários
bin/php.sh tests/run.php http       # só HTTP
bin/php.sh tests/run.php http/Cms   # um arquivo
tests/visual/executar.sh            # visual + responsividade + acessibilidade (Node 22+ e Chrome/Edge)
```

- **Runner próprio** (`tests/run.php`), sem PHPUnit. Cada arquivo retorna `['nome' => fn () => ...]`.
- **Dados isolados:** cada teste usa um diretório temporário. Os testes HTTP sobem o **servidor embutido do PHP** com `TEIA_DATA_DIR` próprio e fazem a instalação real via `/instalar`.
- **SMTP falso:** não se aplica. Não há formulários nem envio de e-mail (decisão confirmada; PENDÊNCIAS P-10).
- **Teste visual:**
  1. `tests/visual/preparar.php` cria uma instância temporária com **conteúdo de teste**: a resposta da 1ª dúvida usa o mesmo texto de layout do Figma, e os PDFs são de teste. Isso reproduz o estado do frame. Esse conteúdo nunca é publicado.
  2. `tests/visual/comparar.mjs` controla o Chrome headless via DevTools Protocol, usando o WebSocket nativo do Node, sem npm.
  3. Ele gera `tests/visual/reports/relatorio.html`, fora do Git.

## Últimos resultados — 12/11/2026

**Ambiente:** Windows 11 Pro 10.0.26200 · PHP 8.3.36 (NTS) · SQLite 3.53.4 · Node 26.13.1 · Google Chrome headless (`--headless=new`).

| Bateria | Resultado |
|---|---|
| Unitários (`tests/unit`) | **55/55** |
| HTTP / integração (`tests/http`) | **55/55** |
| **Total PHP** | **110/110** |
| Visual, responsividade e acessibilidade | **105/105 verificações** |

### Cobertura de segurança (cada controle tem um teste que tenta burlá-lo)

| Controle | Testes |
|---|---|
| XSS | Texto rico com `<script>`, `<img onerror>`, `javascript:`, quebra de atributo; publicação de payloads pelo CMS e verificação do HTML do site |
| CSRF | POST sem token, token de outra sessão, `Origin` e `Referer` estrangeiros, token sem estado adulterado, expirado ou de outra finalidade |
| Upload malicioso | PHP disfarçado de JPG, poliglota GIF+PHP, SVG com script, tamanho e dimensões acima do limite, PDF com JavaScript, texto com extensão .pdf, nome com `../`; remoção de EXIF/GPS conferida |
| Sessão | Flags do cookie, regeneração no login, ID forjado (strict mode), logout invalida no servidor, expiração por inatividade e absoluta, troca de senha encerra outras sessões |
| Força bruta | Bloqueio por usuário mesmo com a senha certa; chaves HMAC sem IP puro; janela de tempo |
| Permissões | Editor tentando GET/POST em página não autorizada, em usuários, backups e auditoria, e tentando se promover a admin; POST direto sem login em todas as rotas sensíveis |
| Revogação | Revogar permissão, desativar conta ou mudar papel encerra a sessão; o último admin não pode ser desativado nem rebaixado |
| CMS | Formulário gerado da definição; validação no servidor; lista branca; listas repetíveis (adicionar, remover, reordenar); histórico e restauração; conflito 409 |
| Instalador | Requisitos; token incorreto; limite de tentativas; CSRF; `Origin`; senha fraca; recusa de dados dentro de `public/`; 404 após instalar |
| Recuperação | Token errado, token correto, uso único e limite de tentativas |
| Backup | Criar, baixar, alterar e restaurar (conteúdo volta); Zip Slip; hash adulterado; versão mais nova; backup sem admin; arquivo que não é ZIP; retenção; cron com código errado ou certo |
| Cabeçalhos | CSP com nonce que muda a cada requisição, nosniff, Referrer-Policy, Permissions-Policy, frame-ancestors; sem X-Powered-By; sem cookies para visitantes; código-fonte não servido |

### Comparação visual com o Figma (1440 px)

| Verificação | Tolerância | Resultado |
|---|---|---|
| Altura das 11 seções | ±4 px | 11/11 |
| Cor de fundo das seções | exata | 11/11 |
| Posição e tamanho de títulos, botões, fotos, mapa, itens do FAQ e cards (24 elementos) | ±2 px | 24/24 |
| Fonte (Geist local) e peso (8 estilos) | exato | 9/9 |
| Altura total da página | — | site 5383 px × Figma 5388,5 px |

Pixels diferentes por seção (limiar de 48 em algum canal):

| Seção | % |
|---|---|
| Cabeçalho | 3,3 |
| Submenu | 5,2 |
| Banner | 2,0 |
| Coworking | 3,5 |
| Podcast | 2,9 |
| Fotografia | 2,4 |
| Música | 2,4 |
| Mapa | 7,1 |
| Dúvidas | 3,1 |
| Normas | 2,8 |
| Rodapé | 3,9 |

As diferenças são, na maior parte:

- suavização de bordas do texto, porque o Figma e o Chrome rasterizam fontes de formas diferentes;
- o **mapa**, que no Figma é uma imagem estática e no site é o Google Maps real (D-05);
- quebra de linha diferente na última linha do texto de teste da 1ª dúvida.

Isso aparece na imagem `diferencas-1440.png` do relatório.

### Responsividade (320, 360, 390, 414, 768, 1024, 1280 e 1440 px)

Em todas as larguras:

- sem rolagem horizontal;
- fonte mínima de 12 px;
- alvos de toque com pelo menos 44 × 44 px abaixo de 1024 px;
- menu mobile abre com o botão e fecha com Esc.

### Acessibilidade

- `lang="pt-BR"`.
- Todas as imagens têm `alt` (as decorativas usam `alt=""`).
- O campo de seleção do mapa tem rótulo e os iframes têm `title`.
- Link "Pular para o conteúdo" é o 1º Tab.
- Foco visível em todos os elementos percorridos por teclado.
- Um único `h1`.
- **Contraste WCAG AA** verificado em todos os textos, em todas as larguras. Para isso foram feitos 2 ajustes em relação ao Figma: D-12 e D-13 em DECISOES_DE_DESIGN.md.

## O que NÃO foi testado (pendências de teste)

| Item | Motivo |
|---|---|
| Servidor real da KingHost (Apache/PHP-FPM, `.htaccess`, permissões, Git Webhook, Cronjob) | Sem acesso ao servidor. Ver checklist em DEPLOY.md. |
| Safari (macOS/iOS) e Firefox | Só o Chrome headless estava disponível. |
| Leitores de tela reais (NVDA, VoiceOver) | Foram feitas verificações automáticas de estrutura, rótulos e foco. |
| Contraste sobre as imagens decorativas do banner e do mapa | O cálculo usa a cor de fundo sólida sob a imagem (`#14171F` e `#141720`). As imagens são formas escuras, de baixo contraste próprio. |
| Painel administrativo nas larguras de celular | A responsividade foi verificada só no CSS. A comparação automática cobre apenas o site público. |
| PHP 8.1 e 8.2 | Os testes rodaram em 8.3. O código evita recursos posteriores à 8.1. |
