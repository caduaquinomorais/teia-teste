# Pendências de conteúdo e escopo — TEIA | ADE SAMPA

Atualizado em 12/11/2026, ao fim da implementação.

**Legenda:**

- 🔴 **Bloqueante para publicar o conteúdo completo:** o site funciona, mas a seção fica incompleta.
- 🟡 **Não bloqueante.**
- ✅ **Resolvida.**

Todas as pendências de conteúdo podem ser resolvidas **pelo painel**, sem programação.

| ID | Tipo | Item | Situação atual | O que falta / como resolver |
|---|---|---|---|---|
| P-01 | 🔴 | Respostas das Dúvidas frequentes | Figma: a 1ª resposta é Lorem ipsum e as demais não existem. **Perguntas sem resposta não aparecem no site** (D-14). | Texto oficial das 4 respostas → Painel → Página do Teia → Dúvidas frequentes |
| P-02 | 🔴 | PDFs "Manual de conduta" e "Uso para gravações" | Os cards aparecem sem o botão "Baixar PDF" (D-15) | Enviar os 2 PDFs → Painel → Normas de uso |
| P-03 | ✅ | Destino de "Faça seu agendamento" e "Agendamentos" | `https://app.adesampa.com.br/welcome` (informado pelo responsável) | — |
| P-04 | 🟡 | Unidades do mapa | Google Maps implementado (D-05), com 1 unidade de exemplo: os dados da imagem do Figma, TEIA Parque Novo Mundo. **O CEP está cortado na imagem** ("02160-0…") e foi omitido. | Lista oficial de unidades (nome, zona, classificação, situação, endereço com CEP) → Painel → Mapa das unidades. Opcional: link de um mapa do Google My Maps. |
| P-05 | 🟡 | Mais fotos por espaço | O Figma mostra 4 pontinhos, mas só há 1 foto por espaço; os pontinhos só aparecem com 2+ fotos (D-06) | Fotos oficiais → Painel → Espaços → Fotos |
| P-06 | ✅ | Cabeçalho institucional | Igual ao Figma, links vazios (`TODO-LINK`) — D-01, aprovado | Preencher os links quando os domínios existirem → Painel → Cabeçalho e rodapé |
| P-07 | ✅ | Rodapé institucional | Igual ao Figma, links vazios (`TODO-LINK`) — D-02, aprovado | Idem |
| P-08 | ✅ | Breadcrumb | Texto do Figma, links editáveis vazios — D-03 | Idem (Página do Teia → Banner) |
| P-09 | 🟡 | URLs das redes sociais | Ícones visíveis e desabilitados (60% de opacidade) | URLs oficiais → Cabeçalho e rodapé → Redes sociais |
| P-10 | ✅ | Formulários públicos | **Confirmado pelo responsável: o TEIA não terá formulários.** Os módulos de formulário e SMTP não foram implementados. | — |
| P-11 | 🟡 | "Termos de uso", "Privacidade (LGPD)" e "Acessibilidade" | Links vazios | URLs das páginas oficiais da ADE SAMPA |
| P-12 | 🟡 | Descrições (alt) das fotos | Descrições neutras escritas a partir do que aparece nas fotos | Revisar ou aprovar no painel |
| P-13 | 🟡 | Título da aba e descrição para buscadores | Título "Teia — Coworkings e estúdios públicos \| ADE SAMPA"; descrição = texto do banner | Revisar ou aprovar no painel (Informações para buscadores) |
| P-14 | 🟡 | Favicon | Não aplicado: há favicons na página "Componentes" do Figma, sem indicação de que sejam do TEIA | Confirmar qual usar (ADE SAMPA ou TEIA) |
| P-15 | ✅ | Layouts de celular e tablet, menu mobile, 404 e estados | Definidos em DECISOES_DE_DESIGN.md (D-06 a D-10) e testados nas 8 larguras | Validar com o design, se desejado |
| P-16 | 🟡 | Resolução da foto "Estúdios de música" | Original de 1024 × 576 (abaixo do ideal de 1086 × 784) | Enviar original maior pelo painel |
| P-17 | 🟡 | Cor TEIA 001 | Style Guide `#1F2533` × variável aplicada `#14171F`. Usada a variável aplicada. | Confirmar com o design |
| P-18 | 🟡 | Grafia "Atendimen**r**o ao empreendedor" (rodapé) | Copiada exatamente do Figma | Corrigir pelo painel se for erro de digitação |
| P-19 | 🟡 | "Mapa de unidades" (submenu) × "Mapa dos Teias" (botões) | Mantidos como no Figma | Confirmar |
| P-20 | 🟡 | Contraste de 2 elementos do Figma | Ajustados por acessibilidade (D-12, D-13) | Validar com o design |

## Fora do escopo (não implementado)

- Home, Sobre, Comunicação, Publicações, Transparência, Notícias, Produtos Digitais, Sampa Empreende (e cursos), Sampa Tech, Vai Tec, Sampa Games e o mega-menu "Menu de projetos".
- "Agendamentos (Login)": o mapa do site do Figma cita essa área, mas o agendamento é o sistema externo `app.adesampa.com.br`.
