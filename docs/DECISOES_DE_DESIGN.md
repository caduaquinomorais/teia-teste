# Decisões de design — TEIA | ADE SAMPA

Decisões tomadas onde o Figma não tem especificação, ou onde foi preciso ajustar o design por acessibilidade.

**Status:**

- **Aprovada:** confirmada pelo responsável em 12/11/2026.
- **Adotada:** decisão técnica de baixo risco.
- **Ajuste de acessibilidade:** desvio intencional do Figma para cumprir o WCAG AA.

---

## D-01 — Cabeçalho institucional idêntico ao Figma · **Aprovada**

**Decisão do responsável:** o site do Teia ficará em `www.<domínio>/teia`, e os outros projetos em outros domínios ou subdomínios. Por isso o cabeçalho e o rodapé ficam **iguais ao Figma**.

- Os links institucionais (Home, Sobre, Programas, Comunicação, Transparência e o logo) ficam **vazios** e marcados no código como `TODO-LINK`.
- Esses links são editáveis no painel em *Cabeçalho e rodapé*.
- Link vazio: o item aparece, mas não navega (`<a>` sem `href`, com `data-link-pendente`).
- "Programas" fica destacado como seção atual (sublinhado azul), como no Figma.
- O botão "Agendamentos" leva a `https://app.adesampa.com.br/welcome`.

## D-02 — Rodapé institucional idêntico ao Figma · **Aprovada**

- Mantém as 8 colunas, as redes sociais, os links legais, o CNPJ, o endereço e os logos, com os textos exatos do Figma, inclusive a grafia "Atendimen**r**o" (P-18).
- Todos os links começam vazios (`TODO-LINK`).
- As cores das barras de cada coluna são identidade visual: ficam fixas no template e o CMS não as altera.
- Redes sociais sem link aparecem com opacidade de 60% (indicação visual de inativas).

## D-03 — Breadcrumb · **Aprovada**

O texto "Início / Programas / Teia" é mantido como no Figma. "Início" e "Programas" têm links editáveis, inicialmente vazios (`TODO-LINK`). O item atual usa `aria-current="page"`.

## D-04 — Página única com âncoras · **Adotada**

- Âncoras: `#coworking`, `#podcast`, `#fotografia`, `#musica`, `#mapa`, `#duvidas` e `#normas`.
- O botão "Mapa dos Teias" leva a `#mapa`.
- A rolagem é suave, respeitando `prefers-reduced-motion`, e o foco vai para a seção (acessibilidade).
- O identificador de cada espaço é editável no CMS, com validação.

## D-05 — Mapa com Google Maps · **Aprovada**

**Decisão do responsável:** a seção de mapa terá um Google Maps. A listagem de endereços salvos será alimentada depois.

- O quadro de 638 × 354 px do Figma tem, à esquerda, um **painel de unidade** em HTML que reproduz o painel da imagem do Figma (cabeçalho `#6C6C6C` e corpo `#F5F5F5`). À direita fica o **Google Maps embutido** (iframe sem chave de API) do endereço da unidade.
- **Lista de unidades editável** no CMS: nome, zona, classificação, situação e endereço. Com mais de uma unidade, aparece um seletor que troca o painel e o mapa.
- **Mapa personalizado (opcional):** se a equipe criar um mapa no Google My Maps com todas as unidades, basta colar o link de incorporação. Nesse caso o iframe ocupa o quadro inteiro.
- CSP: `frame-src` liberado só para `www.google.com` e `maps.google.com`, e só na página do mapa.
- Os ícones de "voltar" e "rotas" da imagem do Figma **não** foram recriados: eram parte da interface do Google, e não assets do projeto.

## D-06 — Carrossel de fotos · **Adotada**

- Cada espaço tem uma lista de fotos no CMS.
- Os pontinhos só aparecem com **2 fotos ou mais**. A linha de pontos mantém os 8 px de altura mesmo vazia, preservando a geometria do Figma.
- Funciona com pontos clicáveis (`aria-pressed`), setas do teclado e gesto de arrastar. Não há rotação automática, por acessibilidade.
- Pontos: 8 px; ativo `#14171F` e inativo `#A3AAB6` (dos SVGs do Figma). Abaixo de 1024 px, a área de toque é de 44 × 44 px.

## D-07 — FAQ em acordeão · **Adotada**

- Usa `<details>/<summary>`, que funciona sem JavaScript, com o ícone CaretCircleDown girando 180° quando aberto.
- Todos começam fechados. No Figma o 1º aparece aberto, como ilustração do estado.
- A numeração "1., 2., …" é automática.

## D-08 — Estados de interação · **Adotada**

- Botões do Teia: estados do componente `241:4030`.
  - Primário: hover `#A3AAB6` com texto `#14171F`.
  - Secundário: hover com borda de 2 px, feita com sombra interna para não deslocar o layout.
- Botão "Agendamentos": hover `#042F80`.
- Links do menu: hover azul `#1158D4`. Submenu e rodapé: sublinhado.
- **Foco visível:** contorno de 2 px `#1158D4` com afastamento de 2 px; sobre fundos escuros, contorno branco.

## D-09 — Responsividade · **Adotada**

O Figma só tem o desktop de 1440 px.

| Largura | Comportamento |
|---|---|
| ≥ 1280 px | Fiel ao Figma: conteúdo de 1110 px centralizado (165 px de margem em 1440) |
| 1024–1279 px | Margens fluidas; colunas proporcionais; rodapé em 4 colunas |
| 768–1023 px | **Menu "Menu"** com painel suspenso (fecha com Esc ou clique fora); submenu com rolagem horizontal própria; espaços empilhados (foto acima do texto); mapa abaixo do texto; rodapé em 3 colunas; alvos de toque de 44 px |
| < 768 px | Uma coluna; margem de 16 px; Display 61→40 px, H2 39→28 px, Body 1 20→18 px; botões em largura total; mapa com painel acima e mapa abaixo; rodapé em 2 colunas (1 abaixo de 360 px) |

**Sem JavaScript:** o menu aparece expandido, porque a classe `no-js` é removida pelo script.

## D-10 — Página 404 · **Adotada**

Usa o cabeçalho, o submenu e o rodapé, com uma seção no estilo do banner: etiqueta "Erro 404", título, texto e botão "Voltar ao início". Os textos são editáveis no CMS (página "erro-404").

## D-11 — Fundos do banner e do mapa · **Adotada**

As formas decorativas são imagens exportadas do Figma, servidas como `<picture>` (WebP + JPG) posicionado atrás do conteúdo, sem estilo inline (compatível com a CSP). O fundo do mapa fica com 70% de opacidade sobre `#141720`, como no Figma. A imagem do banner é editável no CMS.

## D-12 — Contraste dos links do rodapé · **Ajuste de acessibilidade**

O Figma usa `rgba(207,230,255,0.4)` sobre `#001B4D`, com contraste de **3,1:1**. Para texto de 12 px, o WCAG AA exige 4,5:1. A opacidade foi elevada para **0,6**, chegando a **5,4:1**. O token original continua documentado (`--empreende-n200-40`) e é usado na borda do cabeçalho.

## D-13 — Rótulos do painel de unidade do mapa · **Ajuste de acessibilidade**

Na imagem do Figma, os rótulos ("Unidade", "ZONA"…) aparecem em `#9E9E9E` sobre `#F5F5F5`, com contraste de **2,5:1**. A cor usada é **`#616161`**, com contraste de cerca de 5,7:1.

## D-14 — Perguntas sem resposta não são publicadas · **Adotada**

No Figma, a resposta 1 é *Lorem ipsum* e as demais não existem. Para não publicar texto provisório nem conteúdo inventado, **uma pergunta só aparece no site quando tem resposta** (P-01). Enquanto não houver respostas, a seção mostra só o título e o subtítulo.

## D-15 — Botão "Baixar PDF" só com arquivo · **Adotada**

Os cards de Normas aparecem sempre, mas o botão só aparece quando há um PDF enviado no CMS (P-02). O bloco de texto dos cards mantém a altura de 141 px do Figma, para que os cards tenham a mesma altura.

## D-16 — Painel administrativo · **Adotada**

O painel não existe no Figma. Ele usa a identidade do Teia (fonte Geist, `#14171F`, botões do componente Teia) com um layout utilitário: barra superior, cartões e formulários. Formulários longos têm a barra "Publicar alterações" fixa no rodapé da tela.
