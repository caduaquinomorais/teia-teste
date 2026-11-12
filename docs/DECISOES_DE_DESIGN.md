# Decisões de design — TEIA | ADE SAMPA

Este documento registra as decisões tomadas onde o Figma não tem especificação. Status:
**Proposta** (aguarda aprovação, pois afeta escopo ou conteúdo institucional) · **Adotada** (decisão técnica de baixo risco).

---

## D-01 — Cabeçalho adaptado ao TEIA · **Proposta** (ref. P-06)

O `menu header` do Figma é o cabeçalho institucional da ADE SAMPA, com links para Home, Sobre, Programas, Comunicação e Transparência.
Proposta:
- Manter o visual (fundo branco, altura 68px, logo ADE SAMPA à esquerda, botão azul "Agendamentos" à direita).
- **Remover** os links Home, Sobre, Programas, Comunicação e Transparência.
- Logo ADE SAMPA sem link (ou levando ao topo da página do TEIA).
- O submenu TEIA (Coworking, Podcast, Fotografia, Música, Mapa de unidades, Dúvidas, Normas de uso) vira a navegação principal, com âncoras para as seções.

## D-02 — Rodapé adaptado ao TEIA · **Proposta** (ref. P-07)

Proposta:
- Manter: fundo `#001B4D`, logo ADE SAMPA, texto institucional, redes sociais, links legais, razão social/CNPJ/endereço/copyright e logos ADE SAMPA + Prefeitura.
- **Remover** as 8 colunas de links para outras áreas e programas (ADE SAMPA, Sampa Empreende, Sampa Tech, Sampa Games, Sampa Verde, Sampa Criativa, Outros programas, Comunicação).
- Com isso, as cores de outros programas (Tech, Games, Verde, Criativa) deixam de ser usadas.

## D-03 — Breadcrumb · **Proposta** (ref. P-08)

Manter o texto "Início / Programas / Teia" como está no Figma, **sem links** em "Início" e "Programas", marcado como `nav aria-label="Trilha"`, com "Teia" em `aria-current="page"`.

## D-04 — Página única com âncoras · **Adotada**

O Figma tem uma única página do TEIA. O site será uma landing page com âncoras: `#coworking`, `#podcast`, `#fotografia`, `#musica`, `#mapa`, `#duvidas`, `#normas`. O botão "Mapa dos Teias" leva a `#mapa`. A rolagem é suave, com `scroll-margin-top` para compensar o cabeçalho fixo (se adotado) e respeitando `prefers-reduced-motion`.

## D-05 — Seção do mapa · **Proposta** (ref. P-04)

O Figma mostra uma imagem estática de mapa com os dados de uma única unidade. Opções:
- **(A, recomendada)** Mostrar a imagem do Figma, editável no CMS, e uma **lista de unidades** editável (nome, zona, classificação, status, endereço com link "Ver no mapa" para o Google Maps). Não usa script de terceiros, fica compatível com a CSP e não exige consentimento de cookies.
- (B) Só a imagem estática do Figma, editável no CMS. Mais fiel ao design, mas mostra uma única unidade.
- (C) Mapa interativo (Google Maps/OpenStreetMap). Exige script de terceiros, relaxar a CSP, análise de LGPD e possivelmente chave de API paga. **Fora da stack fechada** ("sem dependências JS via CDN"), por isso não é recomendada.

## D-06 — Carrossel de fotos · **Adotada** (ref. P-05)

Cada espaço tem uma **lista de fotos** no CMS. Com 1 foto, os pontos não aparecem. Com 2 ou mais, o carrossel funciona sem dependências: pontos clicáveis (`button` com `aria-label="Foto N de M"`), suporte a teclado e swipe, e sem rotação automática (acessibilidade). Pontos de 8px; o ativo é `#14171F` e o inativo `#A3AAB6`, conforme os SVGs exportados. A área de toque dos pontos chega a 44px com padding invisível.

## D-07 — FAQ em acordeão · **Adotada**

Implementado com `<details>`/`<summary>`, que funciona sem JavaScript. Fechado mostra o ícone CaretCircleDown; aberto mostra o mesmo ícone girado 180° (como no item 1 do Figma). Todos começam fechados.

## D-08 — Estados de interação · **Adotada**

- Botões TEIA: usar os estados do componente `241:4030` (ver `DESIGN_TOKENS.md` §8).
- Botão "Agendamentos" do cabeçalho: estados "navegação (hover)" e "navegação (pressed)" do componente `Botão (Empreende)`, a extrair na F2.
- Links do submenu e do rodapé: sublinhado no hover (sem especificação no Figma).
- **Foco visível:** contorno de 2px `#1158D4` com `outline-offset: 2px` em todos os elementos interativos; sobre fundo escuro, contorno branco.

## D-09 — Responsividade · **Adotada** (ref. P-15)

| Largura | Comportamento |
|---|---|
| ≥ 1280px | Fiel ao Figma (gutter de 165px a partir de 1440px; container de 1110px centralizado) |
| 1024–1279px | Gutter fluido `clamp(24px, 8vw, 165px)`; colunas mantidas com larguras proporcionais |
| 768–1023px | Seções dos espaços empilhadas (foto acima do texto, sempre na mesma ordem); mapa abaixo do texto; cards de PDF em 2 colunas |
| < 768px | Uma coluna; gutter de 16–24px; Display 61→40px, H2 39→28px, Body 1 20→18px (mínimo de 12px em todo o site); botões em largura total; cards de PDF em 1 coluna; rodapé empilhado |

Menu mobile (< 1024px): o submenu TEIA vira uma barra com rolagem horizontal, ou um botão "Menu" com painel. A escolha será feita na F2, documentada e validada nas larguras de teste. Alvos de toque de 44px ou mais.

## D-10 — Página 404 · **Adotada**

Usa o cabeçalho, o submenu e o rodapé, com uma seção no estilo do banner (fundo `#14171F`): título "Página não encontrada", texto curto e botão primário "Voltar ao início". Esse texto é funcional, não institucional, e fica editável no CMS.

## D-11 — Fundo do banner e do mapa · **Adotada**

As formas decorativas do banner e do mapa são imagens raster exportadas do Figma. Serão servidas como `background-image` (WebP com fallback JPG) com `background-size: cover`. São decorativas, então ficam sem alt.
