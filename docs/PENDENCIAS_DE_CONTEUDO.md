# Pendências de conteúdo e escopo — TEIA | ADE SAMPA

Atualizado em 12/11/2026 (fim da F0).

**Legenda:**
- 🔴 **Bloqueante:** impede publicar a funcionalidade como está no Figma.
- 🟡 **Não bloqueante:** dá para implementar com uma decisão documentada ou um estado vazio.
- ❓ **Decisão de escopo:** precisa de aprovação do responsável pelo projeto.

| ID | Tipo | Item | Situação no Figma | O que é necessário | Comportamento até resolver |
|---|---|---|---|---|---|
| P-01 | 🔴 | Respostas do FAQ | Pergunta 1 com **Lorem ipsum**; perguntas 2 a 4 sem resposta | Texto oficial das 4 respostas | Pergunta sem resposta não é publicada (o CMS exige a resposta) |
| P-02 | 🔴 | PDFs "Manual de conduta" e "Uso para gravações" | Só o card; nenhum arquivo | Os 2 arquivos PDF oficiais | Botão "Baixar PDF" fica oculto enquanto não houver arquivo no CMS |
| P-03 | 🔴❓ | Destino de "Faça seu agendamento" (5 botões) e de "Agendamentos" (cabeçalho) | Sem link. O mapa do site cita "Agendamentos (Login)", mas não há frame desenhado | URL do sistema de agendamento existente, **ou** confirmação de que um sistema de agendamento entra no escopo (mudança grande de escopo e custo) | Link configurável no CMS; botão oculto se vazio |
| P-04 | 🔴❓ | Seção "Encontre o Teia mais próximo" | **Imagem estática** de um mapa (estilo Google Maps) com os dados de uma unidade (TEIA Parque Novo Mundo) | (a) Lista oficial de unidades: nome, zona, classificação, status de funcionamento, endereço; (b) decisão de formato: imagem estática, lista de unidades editável no CMS ou mapa interativo de terceiros (Google/OSM: envolve CSP, LGPD/cookies e possível custo de API) | Ver proposta em `DECISOES_DE_DESIGN.md` (D-05) |
| P-05 | 🟡 | Carrossel de fotos dos 4 espaços | 4 pontos de paginação, mas **só 1 foto** por espaço | Mais fotos oficiais (3 por espaço, para bater com os 4 pontos) | Lista de fotos no CMS; os pontos só aparecem com 2 fotos ou mais |
| P-06 | ❓ | Cabeçalho institucional | Links para Home, Sobre, Programas, Comunicação, Transparência (outras áreas) | Aprovar a adaptação para o TEIA | Proposta D-01 em `DECISOES_DE_DESIGN.md` |
| P-07 | ❓ | Rodapé institucional | 8 colunas de links para outras áreas e programas | Aprovar a adaptação para o TEIA | Proposta D-02 |
| P-08 | ❓ | Breadcrumb "Início / Programas / Teia" | "Início" e "Programas" apontam para páginas de outras áreas | Aprovar | Proposta D-03 (texto sem link) |
| P-09 | 🟡 | URLs das redes sociais (Instagram, LinkedIn, YouTube, Facebook) | Só ícones | URLs oficiais | Ícone oculto se a URL estiver vazia no CMS |
| P-10 | ❓ | Formulários públicos (requisito §10 do prompt) | **Nenhum formulário no frame TEIA** | Confirmar que **não haverá formulário**. Se houver, fornecer campos e textos | Sem formulário, os módulos de formulário e SMTP da F4 não são implementados; o backup é mantido |
| P-11 | 🟡 | Links "Termos de uso", "Privacidade (LGPD)", "Acessibilidade" | Texto sem página desenhada | URLs das páginas oficiais da ADE SAMPA, ou conteúdo para páginas próprias | Links configuráveis; ocultos se vazios |
| P-12 | 🟡 | Textos alternativos (alt) das fotos e do mapa | Não definidos | Descrições aprovadas | Alt editável no CMS; padrão descritivo neutro, por exemplo "Foto do coworking público do Teia" |
| P-13 | 🟡 | Título da página e meta description (SEO) | Não definidos | Textos aprovados | Título "Teia — ADE SAMPA"; descrição = texto do banner (campos do CMS) |
| P-14 | 🟡 | Favicon | Há favicons na página "Componentes" (`290:3966`), sem indicação de que sejam do TEIA | Confirmar qual favicon usar | Usar o favicon da página "Componentes" após conferência visual |
| P-15 | 🟡 | Layouts de tablet e mobile, menu mobile, página 404, estados de foco | Não existem | — | Decisões em `DECISOES_DE_DESIGN.md` |
| P-16 | 🟡 | Resolução da foto "Estúdios de música" | 1024×576 (abaixo de 2× para o bloco de 543px) | Original em resolução maior | Usar a disponível |
| P-17 | 🟡 | Divergência de cor TEIA 001 | Style Guide `#1F2533` × variável aplicada `#14171F`; fundo do mapa `#141720` sem variável | Confirmar com o design | Usar os valores aplicados nos nós |
| P-18 | 🟡 | Erro de digitação no rodapé | "Atendimen**r**o ao empreendedor" | — | Só importa se a coluna for mantida (D-02 propõe removê-la) |
| P-19 | 🟡 | Nomes diferentes para o mapa | Submenu "Mapa de unidades" × botão "Mapa dos Teias" | — | Manter os textos exatamente como estão; os dois levam à mesma seção |
| P-20 | 🟡 | Line-height do bloco "Normas" | Título e subtítulo com "Auto", diferente das outras seções (1.1/1.25) | — | Reproduzir fielmente (`normal`) |

## Itens fora do escopo identificados no arquivo

As áreas Home, Sobre, Comunicação, Publicações, Transparência, Notícia, Produtos Digitais, Sampa Empreende (e cursos), Sampa Tech, Aceleração Vai Tec, Sampa Games e o mega-menu "Menu de projetos" **não serão implementadas**.
