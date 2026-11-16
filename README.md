# Site TEIA | ADE SAMPA

Site institucional do **Teia**, programa de coworkings e estúdios públicos da ADE SAMPA. É uma página única fiel ao Figma.

- **Stack:** PHP puro (8.1+), HTML, CSS e JavaScript sem dependências. Sem banco de dados.
- **Conteúdo:** fica em arquivos PHP simples em `app/content/`, editados por quem desenvolve e publicados via Git.
- **Hospedagem de destino:** KingHost (Apache). Nada precisa ser instalado ou configurado no servidor.
- **Idioma:** português brasileiro.

> Até 12/11/2026 o projeto teve um painel administrativo (CMS), com banco SQLite, usuários e backups. Ele foi **removido a pedido do responsável**. A versão com painel continua disponível na tag Git `com-cms`.

## Requisitos

| Onde | O que |
|---|---|
| Servidor | PHP 8.1 ou superior (só o núcleo do PHP, nenhuma extensão extra) e Apache com `mod_rewrite` |
| Desenvolvimento | PHP 8.1+ e Git. Testes HTTP: extensão `curl`. Testes visuais: Node.js 22+ e Chrome ou Edge. Regerar imagens: extensão `gd` com WebP. |

## 1. Clonar

```bash
git clone <url-do-repositorio> teia-ade-sampa
cd teia-ade-sampa
```

## 2. Ambiente local

1. Instale o PHP 8.1+. No Windows: `winget install PHP.PHP.NTS.8.3`.
2. Teste com `bin/php.sh -v`.
   - O script localiza o PHP (variável `PHP_BIN`, `php` no PATH ou a instalação do winget).
   - Se o PHP não tiver `php.ini`, o script carrega `bin/php-dev.ini`.

## 3. Iniciar o servidor local

```bash
bin/serve.sh          # http://localhost:8080
bin/serve.sh 8000     # outra porta
```

## 4. Editar o conteúdo

| Arquivo | Conteúdo |
|---|---|
| `app/content/teia.php` | Banner, espaços, mapa (unidades), dúvidas, normas (PDFs) e link de agendamento |
| `app/content/layout.php` | Cabeçalho, submenu e rodapé (links institucionais `TODO-LINK`) |
| `app/content/erro-404.php` | Textos da página "não encontrada" |

O passo a passo, com exemplos, está em [docs/CONTEUDO.md](docs/CONTEUDO.md).

## 5. Executar os testes

```bash
bin/php.sh tests/run.php          # testes unitários e HTTP (sem PHPUnit)
tests/visual/executar.sh          # comparação com o Figma, responsividade e acessibilidade
```

O relatório visual é gerado em `tests/visual/reports/relatorio.html`. Veja [docs/TESTES.md](docs/TESTES.md).

## 6. Publicar na KingHost

A publicação é automática: depois do merge de um Pull Request no `main`, o GitHub Actions roda os testes e envia o site por **FTPS** para `www/teia` na KingHost, conferindo em seguida se https://oranilom.com.br/teia/ responde. O `.htaccess` da raiz encaminha tudo para `public/` e bloqueia o código-fonte. Não há instalação nem dados no servidor. Configuração, funcionamento e diagnóstico em [docs/DEPLOY.md](docs/DEPLOY.md).

O `main` é o branch de produção: toda mudança entra por Pull Request e só pode ser mesclada se os testes automáticos (GitHub Actions) passarem.

## Documentação

| Documento | Conteúdo |
|---|---|
| [docs/CONTEUDO.md](docs/CONTEUDO.md) | Como editar textos, imagens, PDFs, unidades e links |
| [docs/ARQUITETURA.md](docs/ARQUITETURA.md) | Estrutura de pastas e fluxo das requisições |
| [docs/DESIGN_TOKENS.md](docs/DESIGN_TOKENS.md) | Cores, tipografia, espaçamentos e medidas do Figma |
| [docs/DECISOES_DE_DESIGN.md](docs/DECISOES_DE_DESIGN.md) | Decisões tomadas onde o Figma não tem especificação |
| [docs/INVENTARIO_FIGMA.md](docs/INVENTARIO_FIGMA.md) | Frames, componentes, textos e assets do TEIA no Figma |
| [docs/SEGURANCA.md](docs/SEGURANCA.md) | Controles de segurança |
| [docs/DEPLOY.md](docs/DEPLOY.md) | Publicação na KingHost: como funciona, configuração, rotina e diagnóstico |
| [docs/PROMPT_DEPLOY_KINGHOST.md](docs/PROMPT_DEPLOY_KINGHOST.md) | Prompt para uma IA reproduzir este deploy (com as armadilhas da KingHost) |
| [docs/MANUTENCAO.md](docs/MANUTENCAO.md) | Atualizações, imagens e logs |
| [docs/TESTES.md](docs/TESTES.md) | Como testar e últimos resultados |
| [docs/PENDENCIAS_DE_CONTEUDO.md](docs/PENDENCIAS_DE_CONTEUDO.md) | Conteúdos e decisões pendentes |

## Links pendentes (TODO-LINK)

O cabeçalho e o rodapé reproduzem o Figma institucional. Os links para as outras áreas da ADE SAMPA estão **vazios de propósito**, porque esses endereços ainda não existem. Eles aparecem no site sem levar a lugar nenhum. Procure por `TODO-LINK` em `app/content/` e preencha quando os endereços forem definidos.
