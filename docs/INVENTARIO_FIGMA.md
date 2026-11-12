# Inventário do Figma — TEIA | ADE SAMPA (F0)

- **Arquivo:** Site - Ade Sampa (`r1NdYceMw9ocdyDDrVQUGW`)
- **Data da leitura:** 12/11/2026
- **Método:** conector oficial do Figma (MCP): `get_metadata`, `get_design_context`, `get_variable_defs`, `get_screenshot`.
- **Sistema operacional da extração:** Windows 11 Pro 10.0.26200

---

## 1. Estrutura do arquivo

| Página Figma | ID | Conteúdo | Relevância para o TEIA |
|---|---|---|---|
| Site | `0:1` | Todos os frames de página (1440px) de todas as áreas, mais um mapa do site em formas | Contém o frame **Teia** |
| Componentes | `64:3324` | Componentes (menu, submenus, botões, tags, logos, rodapé, ícones) | Fornece os componentes usados pelo TEIA |
| Style Guide | `394:2745` | Paleta de cores e guia de fontes | Fornece os tokens |

### 1.1 Frames de nível superior da página "Site"

| Frame | ID | Área | Usar? |
|---|---|---|---|
| **Teia** | `212:2903` | **TEIA** | ✅ **Sim, é a única página do TEIA** |
| Group 193 → Teia: Banner (2) | `709:19381` / `633:15633` | TEIA (cópia solta do banner) | Apenas referência; é igual ao banner do frame principal |
| Group 194 → Teia: Mapa (2) | `709:19382` / `633:15719` | TEIA (cópia solta do mapa) | Apenas referência; é igual à seção do mapa |
| Início | `1:2884` | Home institucional | ❌ Fora do escopo |
| Sobre | `306:4533` | Institucional | ❌ |
| Comunicação | `264:3552` | Institucional | ❌ |
| Publicações | `654:15568` | Institucional | ❌ |
| Transparência / Exemplos de transparência | `689:58711`, `689:60561`, `689:61904` | Institucional | ❌ |
| Exemplo de notícia | `574:43625` | Institucional | ❌ |
| Produtos Digitais | `685:50101` | Institucional | ❌ |
| Sampa Empreende | `412:24382` | Sampa Empreende | ❌ |
| Curso fábrica de negócios / Curso gestão de negócios | `527:13325`, `565:28990` | Sampa Empreende | ❌ |
| Aceleração Vai Tec | `64:2150` | Sampa Tech | ❌ |
| Sampa Tech | `468:12857` | Sampa Tech | ❌ |
| Vertical - Sampa Games | `141:2860` | Sampa Games | ❌ |
| Menu de projetos | `656:19254` | Mega-menu institucional (inclui "Teia" na coluna "Espaços e plataformas") | ❌ Navegação institucional |
| Mapa do site (formas com texto, x = −2500) | `1:4435`… | Arquitetura de informação institucional | Só como contexto (ver §4) |
| Diversos ícones, fotos e logos soltos | — | Outras áreas (Praça Victor Civita, Sampa Ensina etc.) | ❌ |

**Conclusão:** o TEIA tem **1 página** desenhada (frame `212:2903`, 1440 × 5388.5px), uma landing page longa. Não há frames de tablet ou mobile, nem de páginas internas, 404 ou estados.

---

## 2. Seções do frame TEIA

| # | Seção | ID do nó | Tipo | Altura |
|---|---|---|---|---|
| 1 | Cabeçalho institucional (`menu header`, variante "programas") | `212:3239` → símbolo `200:4702` | Componente **compartilhado** ADE SAMPA | 69 |
| 2 | Submenu TEIA (`sub-menus`, variante "Variant6") | `215:3934` → símbolo `215:3894` | Componente com variante **exclusiva** TEIA | 40 |
| 3 | Banner (breadcrumb, tag, título, texto, pílula de logos) | `423:35710` | Exclusivo | 397 |
| 4 | Coworkings públicos (texto à esquerda, foto à direita, fundo branco) | `433:36508` | Exclusivo | 546 |
| 5 | Estúdios de podcast (foto à esquerda, fundo cinza) | `433:37816` | Exclusivo | 548 |
| 6 | Estúdios de fotografia (texto à esquerda, fundo branco) | `433:38583` | Exclusivo | 546 |
| 7 | Estúdios de música (foto à esquerda, fundo cinza) | `433:38600` | Exclusivo | 548 |
| 8 | Encontre o Teia mais próximo de você (mapa) | `438:38623` | Exclusivo | 498 |
| 9 | Dúvidas frequentes (acordeão com 4 itens) | `450:39346` | Exclusivo | 811.5 |
| 10 | Normas de uso dos espaços (2 cards de PDF) | `450:39347` | Exclusivo | 706 |
| 11 | Rodapé institucional | `322:9317` → símbolo `674:24236` | Componente **compartilhado** ADE SAMPA | 679 |

Uma camada oculta (`212:3003` "Mask group", hidden) foi ignorada.

---

## 3. Componentes identificados

| Componente | ID do símbolo | Uso no TEIA | Observação |
|---|---|---|---|
| menu header | `1:4584` (5 variantes) | Cabeçalho | Compartilhado; contém links de outras áreas |
| sub-menus | `64:3342` (variante TEIA = `215:3894`) | Submenu | Itens: Coworking, Podcast, Fotografia, Música, Mapa de unidades, Dúvidas, Normas de uso |
| Botão (Teia) | `241:4030` | Todos os CTAs | 12 estados: primary, secundary e text × padrão, hover, pressed, disabled |
| Botão (Empreende) "navegação" | `472:10795` | Botão "Agendamentos" do cabeçalho | Variantes de hover e pressed existem |
| Tags | `1:4716` (Teia Clara `423:36459`, Teia Médio `433:36473`, Teia Escura `423:36461`) | Tags | "USO 100% GRATUITO" = Clara; banner = Médio |
| TEIA (logo) | `213:663` (preto `213:662`, branco `213:664`) | Submenu | — |
| Logos - ade sampa | `1:3174` | Cabeçalho, banner, rodapé | — |
| Logos - Prefeitura | `1:3215` | Banner, rodapé | — |
| Rodapé | `408:22442` | Rodapé | Compartilhado |
| Botão Redes | `1:4730` (padrão + hover) | Rodapé | Instagram, LinkedIn, YouTube, Facebook |
| CaretCircleDown | `220:4110` | FAQ | Aberto = girado 180° |
| FilePdf | `264:3478` | Cards de normas | — |
| Icones Verticais / Teia | `1:3022` | Não usado no frame | — |
| Favicons 96/64/48/32 | `290:3966`… | Não aplicados ao TEIA | É preciso confirmar se são da ADE SAMPA ou do TEIA |

---

## 4. Mapa do site (formas com texto) e o TEIA

O mapa do site institucional (x ≈ −2500 na página "Site") tem um nó **"Teia: coworking público"** (`1:4475`). Na mesma árvore aparecem os nós "Estúdio de podcast", "Estúdio de fotografia", "Estúdio de música" e **"Agendamentos (Login)"**.

- Os três estúdios foram desenhados como **seções da página única** do TEIA, e não como páginas separadas.
- **"Agendamentos (Login)"** não tem nenhum frame desenhado. Ver pendência P-03.

---

## 5. Assets extraídos

Estão em `docs/figma/assets-brutos/` (pasta local, fora do Git por causa do tamanho), como baixados do Figma. Na F2 serão otimizados (WebP + fallback JPG) e copiados para `public/assets/`.

| Arquivo | Formato real | Dimensão | Uso |
|---|---|---|---|
| banner-fundo.jpg | JPEG | 2880×1030 | Fundo do banner (formas sobre `#14171F`) |
| mapa-fundo.jpg | JPEG | 2880×1030 | Fundo da seção do mapa (a 70% sobre `#141720`) |
| mapa-painel.png | PNG | 638×451 | Imagem estática do mapa: painel "TEIA Parque Novo Mundo" |
| mapa-recorte.png | PNG | 696×898 | Imagem estática do mapa: recorte do mapa de São Paulo |
| foto-coworking.jpg | JPEG | 4096×2731 | Foto "Coworkings públicos" |
| foto-podcast.jpg | JPEG | 4096×2731 | Foto "Estúdios de podcast" |
| foto-fotografia.jpg | JPEG | 1600×901 | Foto "Estúdios de fotografia" (também aparece por baixo das outras fotos) |
| foto-musica.png | PNG | 1024×576 | Foto "Estúdios de música" (resolução abaixo de 2× para 543px) |
| logo-teia-preto.svg / logo-teia-branco.svg / logo-teia-submenu.svg | SVG | — | Logo TEIA |
| logo-adesampa-header.svg / -banner.svg / -rodape.svg / -rodape-topo.svg | SVG | — | Logo ADE SAMPA (cores diferentes) |
| logo-prefeitura-banner.svg / -rodape.svg | SVG (≈ 415 KB, vetorial) | — | Logo da Prefeitura de São Paulo |
| icone-seta-menu.svg | SVG | — | Seta dos menus suspensos |
| dot-ativo.svg / dot-inativo.svg | SVG | 8px | Pontos do carrossel (serão feitos em CSS) |
| caret-circle-aberto.svg / -fechado.svg | SVG | 32px | Ícone do FAQ |
| icone-file-pdf.svg | SVG | 32px | Ícone de PDF |
| rede-instagram/linkedin/youtube/facebook.svg | SVG | — | Ícones do rodapé |

Verificação de segurança dos SVGs: nenhum contém `<script>`, `foreignObject`, `<image>` ou referência externa (verificado com grep). A sanitização completa será feita na F2.

**Captura de referência:** `tests/visual/reference/teia-1440.png` (1440 × 5389, frame `212:2903` inteiro).

---

## 6. Textos do TEIA (cópia literal do Figma)

### Cabeçalho (compartilhado)
Home · Sobre · Programas ▾ · Comunicação ▾ · Transparência · [Agendamentos]

### Submenu
[logo TEIA] Coworking · Podcast · Fotografia · Música · Mapa de unidades · Dúvidas · Normas de uso

### Banner
- Breadcrumb: Início / Programas / **Teia**
- Tag: ESPAÇOS PÚBLICOS DE TRABALHO
- Título: Teia
- Texto: Um programa gratuito do Sampa Empreende, ligado à Prefeitura de São Paulo e executado pela Ade Sampa, que ajuda pequenos empreendedores e MEIs.

### Espaços (as 4 seções têm a tag "USO 100% GRATUITO" e os botões "Mapa dos Teias" + "Faça seu agendamento")
1. **Coworkings públicos**: Espaço compartilhado focado no networking e produtividade. Conta com mesas de trabalho, Wi-Fi de alta velocidade, ambiente climatizado e salas de reunião disponíveis para uso dos empreendedores.
2. **Estúdios de podcast**: Salas com tratamento acústico, microfones padrão broadcast, fones de retorno e mesas de som multipista. Ideal para gravação de áudio, videocasts e entrevistas.
3. **Estúdios de fotografia**: Equipado com fundo infinito nas cores branca, preta e verde (chroma key), além de kits de iluminação softbox contínua e tripés para celular e câmera. Perfeito para catálogos de e-commerce e produtos.
4. **Estúdios de música**: Infraestrutura voltada a músicos locais e produtores independentes. Isolamento acústico reforçado, monitores de áudio de precisão, interface de gravação profissional e bateria residente em unidades selecionadas.

### Mapa
- Título: Encontre o Teia mais próximo de você
- Texto: Espaços estruturados com Wi-Fi veloz, computadores, salas de reunião compartilhadas e suporte técnico nas regiões periféricas e centrais de São Paulo.
- Botão: Faça seu agendamento
- Imagem estática com: "TEIA Parque Novo Mundo" · Unidade: TEIA Parque Novo Mundo · ZONA: Norte I · Classificação: TEIA - Espaço de Trabalho · Atendimento Funcionando?: Atendimento funcionando · Endereço: Av. Ernesto Augusto Lopes, 100 - Parque Vila Maria, São Paulo - SP, 02160-0… (cortado na imagem)

### Dúvidas frequentes
- Título: Dúvidas frequentes
- Subtítulo: Encontre respostas rápidas para as principais dúvidas sobre o Teia.
- 1. O uso do Teia é totalmente gratuito? → resposta em **Lorem ipsum** (não será publicada)
- 2. Preciso ter um CNPJ para utilizar? → sem resposta
- 3. Como funciona a reserva de salas de reunião? → sem resposta
- 4. É possível fazer uma reserva para o dia todo? → sem resposta

### Normas de uso dos espaços
- Título: Normas de uso dos espaços
- Texto: Para garantir um ambiente colaborativo produtivo e respeitoso para todos, solicitamos a leitura das regras vigentes antes de realizar seu agendamento.
- Card 1: **Manual de conduta**: Diretrizes de convivência, consumo de alimentos, uso das mesas compartilhadas e cuidados básicos. [Baixar PDF]
- Card 2: **Uso para gravações**: Termos de responsabilidade para uso dos estúdios (podcast, fotografia e música) e política de conservação dos equipamentos públicos. [Baixar PDF]

### Rodapé (compartilhado)
- Texto institucional: Entidade vinculada à Prefeitura de São Paulo, por meio da Secretaria Municipal de Desenvolvimento Econômico e Trabalho (SMDET), dedicada à aceleração de negócios, inclusão produtiva e redução das desigualdades regionais.
- Redes sociais: Instagram, LinkedIn, YouTube, Facebook
- Colunas: ADE SAMPA (Início, Sobre nós, Transparência) · Sampa Empreende (7 links) · Sampa Tech (1) · Sampa Games (3) · Sampa Verde (4) · Sampa Criativa (2) · Outros programas (Teia: coworkings e estúdios públicos, Sampa Ensina, Gestão Sampa, Data Sampa, Praça Victor Civita) · Comunicação (Notícias, Publicações, Manual de marca, Manual de redação, Contato)
- Links legais: Termos de uso • Privacidade (LGPD) • Acessibilidade
- **ADE SAMPA – Agência São Paulo de Desenvolvimento** | CNPJ: 21.154.061/0001-83
- Sede Administrativa: Rua Líbero Badaró, 425 - 11º andar - Centro Histórico, São Paulo - SP, CEP 01009-000
- © 2026 ADE SAMPA • Prefeitura de São Paulo. Todos os direitos reservados.

---

## 7. Formulários

**O frame TEIA não tem nenhum formulário.** Pela regra 10 do escopo ("não inventar formulários"), nenhum formulário público será criado sem aprovação. Ver pendência P-10.
