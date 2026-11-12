# Design Tokens — TEIA | ADE SAMPA

Fonte: Figma "Site - Ade Sampa" (`r1NdYceMw9ocdyDDrVQUGW`), frame **Teia** (`212:2903`), página **Componentes** (`64:3324`) e página **Style Guide** (`394:2745`).
Extraído em 12/11/2026 via conector oficial do Figma (`get_variable_defs` + `get_design_context`).

> Convenção: o nome entre parênteses é o nome da variável/estilo no Figma. Valores marcados com ⚠️ têm divergência registrada em `PENDENCIAS_DE_CONTEUDO.md`.

---

## 1. Cores

### 1.1 Paleta TEIA (identidade do programa)

| Token CSS | Variável Figma | Valor | Uso no frame TEIA |
|---|---|---|---|
| `--teia-001` | Teia/001 | `#14171F` | Texto principal, botão primário, fundo do bloco "Normas", borda dos botões/tags |
| `--teia-100` | Teia/-100 | `#3A3E4A` | Tag do banner, pílula de logos, cards de PDF, texto do submenu |
| `--teia-200` | Teia/-200 | `#646B77` | Borda dos cards de PDF, texto de botão desabilitado |
| `--teia-300` | Teia/-300 | `#A3AAB6` | Borda dos itens do FAQ, borda do mapa, fundo do botão primário em hover |
| `--teia-400` | Teia/-400[30%] | `#A3AAB6` a 30% sobre `#FFFFFF` = **`#E3E6E9`** | Fundo do submenu, das seções cinza (podcast, música) e do FAQ |
| `--teia-mapa-fundo` | — (valor solto) | `#141720` ⚠️ | Fundo da seção "Encontre o Teia" (sob a imagem a 70%) |

⚠️ O Style Guide mostra a amostra "TEIA 001" como `#1F2533`, mas a variável aplicada no frame é `#14171F`. **Adotamos a variável (`#14171F`)**, pois é ela que está aplicada nos elementos.

### 1.2 Neutros (Adicionais)

| Token CSS | Variável Figma | Valor | Uso |
|---|---|---|---|
| `--branco` | Adicionais/Branco 001 | `#FFFFFF` | Fundos claros, texto sobre escuro |
| `--preto-001` | Adicionais/Preto 001 | `#262930` | Itens do menu do cabeçalho, descrição "Estúdios de música" |
| `--preto-100` | Adicionais/Preto -100 | `#8F9299` | Borda das fotos, borda de botão secundário desabilitado |
| `--preto-200` | Adicionais/Preto -200 | `#D2D3D6` | Bordas superior/inferior das seções cinza, fundo do botão primário desabilitado |

### 1.3 Cores institucionais ADE SAMPA (cabeçalho e rodapé compartilhados)

| Token CSS | Variável Figma | Valor | Uso |
|---|---|---|---|
| `--empreende-001` | Empreende/001 | `#1158D4` | Botão "Agendamentos" do cabeçalho, sublinhado do menu ativo, barras do rodapé, borda dos botões de redes |
| `--empreende-100` | Empreende/100 | `#042F80` | Fundo dos botões de redes sociais |
| `--empreende-200` | Empreende/200 | `#001B4D` | Fundo do rodapé |
| `--empreende-n200` | Empreende/-200 | `#BEDDFF` | (citado no rodapé) |
| `--empreende-n200-40` | Empreende/-200 [40%] | `rgba(207, 230, 255, 0.4)` | Links do rodapé, borda inferior do cabeçalho |
| `--empreende-n300` | Empreende/-300 | `#F4F9FF` | (citado no banner, sem aplicação visível) |

### 1.4 Cores de outros programas (apenas contexto)

`Tech/001 #00B1E2`, `Games/001 #9B32E0`, `Verde/001 #52AF27`, `Criativa/001 #FB5F1C`. Só aparecem nas barras das colunas do rodapé institucional. **Não serão usadas** se o rodapé for adaptado ao TEIA (decisão pendente, ver `PENDENCIAS_DE_CONTEUDO.md`).

---

## 2. Tipografia

**Família única:** Geist (Google Fonts, licença SIL OFL 1.1). Fonte: https://fonts.google.com/specimen/Geist
Pesos usados no TEIA: **400 (Regular)**, **500 (Medium, apenas as barras "/" do breadcrumb)**, **600 (SemiBold)** e **700 (Bold)**.
Hospedagem: WOFF2 local em `public/assets/fonts/`, `font-display: swap` (a fazer na F2).

### 2.1 Escala

| Token | Estilo Figma | Peso | Tamanho | Line-height | Letter-spacing | Uso no TEIA |
|---|---|---|---|---|---|---|
| `--fs-display` | Display | 600 | 61px | 1 | 0 | Título "Teia" do banner |
| `--fs-h1` | Header 1 | 700 | 48px | — | 0 | Não usado no TEIA |
| `--fs-h2` | Header 2 | 600 | 39px | 1.1 | 0 | Títulos de seção (espaços, mapa, FAQ, normas ⚠️) |
| `--fs-h3` | Header 3 | 600 | 32px | — | 0 | Não usado no TEIA |
| `--fs-h4` | Header 4 | 700 | 26px | — | 0 | Não usado no TEIA |
| `--fs-h5` | Header 5 | 700 | 20px | 1.2 | 0 | Perguntas do FAQ, títulos dos cards de PDF |
| `--fs-body1` | Body 1 | 400 | 20px | 1.25 | 0 | Parágrafos principais |
| `--fs-body2` | Body 2 | 400 | 16px | 1.25 | 0 | Texto dos cards de PDF |
| `--fs-botao` | Botão | 600 | 16px | 1 | 0 | Botões "Botão (Teia)" |
| `--fs-nav` | Navegação | 600 | 14px | 1 | −2% → `-0.02em` (−0.28px) | Menu do cabeçalho, submenu TEIA, botão "Agendamentos" |
| `--fs-tag` | Tag | 700 | 13px | 1 | 0, `text-transform: uppercase` | Tags "ESPAÇOS PÚBLICOS…", "USO 100% GRATUITO" |
| `--fs-caption` | Caption 1 | 400 | 12px | 1.25 | 0 | Breadcrumb, links e textos do rodapé |
| `--fs-caption-bold` | Caption 1 (Bold) | 700 | 12px | normal ⚠️ | 0 | Último item do breadcrumb, títulos das colunas do rodapé |

⚠️ No bloco "Normas de uso dos espaços", o título (39px) e o subtítulo (20px) estão com line-height **Auto (normal)**, diferente do Header 2 (1.1) aplicado nas outras seções. Reproduziremos como está no Figma (`line-height: normal`).
⚠️ "Caption 1 (Bold)" declara `lineHeight: 100` na variável, mas os nós aplicam `leading-normal`. Seguimos o nó (`normal`).

### 2.2 Conversões aplicadas

- Line-height "Auto" → `normal`.
- Line-height percentual (110%, 120%, 125%) → multiplicador (`1.1`, `1.2`, `1.25`).
- Letter-spacing −2% → `-0.02em`.

---

## 3. Espaçamentos

Os valores abaixo vêm dos paddings e gaps do Auto Layout:

| Token | Valor | Onde aparece |
|---|---|---|
| `--sp-4` | 4px | Gap seta/texto no menu |
| `--sp-6` | 6px | Padding vertical das tags; gap barra/título no rodapé; gap entre links do rodapé |
| `--sp-8` | 8px | Gap título/subtítulo (FAQ, normas, cards PDF); padding-x do separador "/" do breadcrumb |
| `--sp-10` | 10px | Gaps do rodapé |
| `--sp-12` | 12px | Gap entre os pontos do carrossel; padding-x dos itens do submenu; gap dos ícones sociais |
| `--sp-14` | 14px | Padding vertical dos botões |
| `--sp-16` | 16px | Gap tag/título/texto; gap entre botões; gap foto/pontos; padding-x das tags; padding-right do logo no submenu |
| `--sp-24` | 24px | Padding-x dos botões; padding dos cards de PDF; padding-right dos itens do FAQ |
| `--sp-28` | 28px | Gap vertical da coluna de texto do mapa |
| `--sp-32` | 32px | Gap texto/botões; gap entre itens do menu; gap cabeçalho/lista do FAQ; padding vertical dos itens do FAQ |
| `--sp-40` | 40px | Gap interno do bloco "Normas"; padding-left dos itens do FAQ |
| `--sp-48` | 48px | Gap do banner; padding-x do cabeçalho/submenu; padding superior e lateral do bloco "Normas" |
| `--sp-62` | 62px | Padding superior do rodapé |
| `--sp-64` | 64px | Padding vertical das seções; padding inferior do bloco "Normas"; gap das linhas do rodapé |
| `--sp-72` | 72px | Padding vertical do banner e da seção do mapa |
| `--sp-88` | 88px | Padding inferior do rodapé |

---

## 4. Raios

| Token | Valor | Uso |
|---|---|---|
| `--radius-btn` | 4px | Botões |
| `--radius-sm` | 6px | Caixa do ícone de PDF, botões de redes sociais |
| `--radius-md` | 8px | Itens do FAQ, imagem do mapa |
| `--radius-tag` | 13px | Tags |
| `--radius-lg` | 16px | Fotos dos espaços, bloco "Normas", cards de PDF |
| `--radius-pill` | 54.545px (≈ pílula) | Pílula de logos do banner (altura 85.245px) → `9999px` |

---

## 5. Bordas e sombras

| Elemento | Borda |
|---|---|
| Tag clara ("USO 100% GRATUITO") | 0.5px `--teia-001` |
| Botão secundário | 1px `--teia-001` (hover: 2px) |
| Foto dos espaços | 1px `--preto-100` |
| Item do FAQ | 1px `--teia-300` |
| Imagem do mapa | 1px `--teia-300` |
| Card de PDF | 1px `--teia-200` |
| Seções cinza | 1px `--preto-200` (topo e base) |
| Cabeçalho | 1px `--empreende-n200-40` (base) |
| Botão de rede social | 0.75px `--empreende-001` |

**Sombras:** nenhuma sombra foi encontrada no frame TEIA.

---

## 6. Larguras, containers e dimensões

| Token | Valor | Origem |
|---|---|---|
| `--frame-largura` | 1440px | Largura do frame desktop |
| `--gutter-desktop` | 165px | Padding lateral das seções (1440 − 2×165 = 1110) |
| `--container` | 1110px | Largura útil do conteúdo |
| `--container-max` | 1116px | `max-width` do cabeçalho, submenu e bloco "Normas" |
| `--header-altura` | 68px + 1px de borda | Cabeçalho |
| `--submenu-altura` | 40px | Submenu TEIA |
| Coluna de texto (espaços) | 508px | — |
| Foto (espaços) | 543 × 392px | Bloco da foto |
| Ponto do carrossel | 8px | — |
| Coluna de texto do banner | 548px (máx. 620px) | — |
| Pílula de logos | 391 × 85.245px | — |
| Coluna de texto do mapa | 410px | — |
| Imagem do mapa | 638 × 354px | — |
| Grade dos cards de PDF | máx. 600px, 2 colunas, gap 16px | — |
| Subtítulo do bloco "Normas" | máx. 800px | — |
| Logo TEIA (submenu) | 47.29 × 14.169px | — |
| Logo ADE SAMPA (cabeçalho) | 123 × 21.224px | — |
| Botão | altura 44px, largura mín. 112px | — |
| Ícone do FAQ | 32px em área de 36px | — |
| Ícone de PDF | 32px em caixa de 48px | — |
| Botão de rede social | 32px | — |

### 6.1 Alturas das seções (referência para o teste visual, ±4px)

| Seção | y inicial | Altura |
|---|---|---|
| Cabeçalho (menu header) | 0 | 69 |
| Submenu TEIA | 69 | 40 |
| Banner | 109 | 397 |
| Coworkings públicos | 506 | 546 |
| Estúdios de podcast | 1052 | 548 |
| Estúdios de fotografia | 1600 | 546 |
| Estúdios de música | 2146 | 548 |
| Encontre o Teia mais próximo | 2694 | 498 |
| Dúvidas frequentes | 3192 | 811.5 |
| Normas de uso dos espaços | 4003.5 | 706 |
| Rodapé | 4709.5 | 679 |
| **Total** | — | **5388.5** |

---

## 7. Breakpoints

O Figma tem **apenas o layout desktop de 1440px**. Não existem frames de tablet ou mobile.
Breakpoints propostos (decisão técnica, ver `DECISOES_DE_DESIGN.md`):

| Token | Valor | Comportamento |
|---|---|---|
| `--bp-desktop` | ≥ 1280px | Layout do Figma |
| `--bp-laptop` | 1024–1279px | Gutter fluido, colunas mantidas |
| `--bp-tablet` | 768–1023px | Colunas empilhadas, foto acima do texto |
| `--bp-mobile` | < 768px | Uma coluna, menu mobile, escala tipográfica reduzida |

---

## 8. Estados de componentes (Botão TEIA — `241:4030`)

| Variante | Padrão | Hover | Pressionado | Desabilitado |
|---|---|---|---|---|
| Primário | fundo `#14171F`, texto branco | fundo `#A3AAB6`, texto `#14171F` | igual ao padrão | fundo `#D2D3D6`, texto `#646B77` |
| Secundário | fundo branco, borda 1px `#14171F`, texto `#14171F` | borda **2px** `#14171F` | igual ao padrão | borda 1px `#8F9299`, texto `#646B77` |
| Texto | sem fundo, texto `#14171F` | borda 1px `#3A3E4A` | sem fundo | texto `#646B77` |

---

## 9. Variáveis CSS (`:root`)

Bloco que será usado em `public/assets/css/tokens.css` na F2:

```css
:root {
  /* Cores — TEIA */
  --teia-001: #14171F;
  --teia-100: #3A3E4A;
  --teia-200: #646B77;
  --teia-300: #A3AAB6;
  --teia-400: #E3E6E9; /* #A3AAB6 a 30% sobre branco */
  --teia-mapa-fundo: #141720;

  /* Cores — neutros */
  --branco: #FFFFFF;
  --preto-001: #262930;
  --preto-100: #8F9299;
  --preto-200: #D2D3D6;

  /* Cores — institucional ADE SAMPA */
  --empreende-001: #1158D4;
  --empreende-100: #042F80;
  --empreende-200: #001B4D;
  --empreende-n200-40: rgba(207, 230, 255, 0.4);

  /* Tipografia */
  --font: "Geist", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  --fw-regular: 400;
  --fw-medium: 500;
  --fw-semibold: 600;
  --fw-bold: 700;
  --fs-display: 61px;  --lh-display: 1;
  --fs-h2: 39px;       --lh-h2: 1.1;
  --fs-h5: 20px;       --lh-h5: 1.2;
  --fs-body1: 20px;    --lh-body1: 1.25;
  --fs-body2: 16px;    --lh-body2: 1.25;
  --fs-botao: 16px;    --lh-botao: 1;
  --fs-nav: 14px;      --lh-nav: 1;   --ls-nav: -0.02em;
  --fs-tag: 13px;      --lh-tag: 1;
  --fs-caption: 12px;  --lh-caption: 1.25;

  /* Espaçamentos */
  --sp-4: 4px;   --sp-6: 6px;   --sp-8: 8px;   --sp-10: 10px;
  --sp-12: 12px; --sp-14: 14px; --sp-16: 16px; --sp-24: 24px;
  --sp-28: 28px; --sp-32: 32px; --sp-40: 40px; --sp-48: 48px;
  --sp-62: 62px; --sp-64: 64px; --sp-72: 72px; --sp-88: 88px;

  /* Raios */
  --radius-btn: 4px;
  --radius-sm: 6px;
  --radius-md: 8px;
  --radius-tag: 13px;
  --radius-lg: 16px;
  --radius-pill: 9999px;

  /* Layout */
  --gutter: 165px;
  --container: 1110px;
  --container-max: 1116px;
  --header-h: 68px;
  --submenu-h: 40px;
}
```
