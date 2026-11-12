# Site TEIA | ADE SAMPA

Site institucional do **Teia**, programa de coworkings e estúdios públicos da ADE SAMPA. É uma página única, feita a partir do Figma, com um painel próprio (CMS) para que pessoas sem conhecimento técnico editem o conteúdo.

- **Stack:** PHP puro (8.1+), SQLite (PDO), HTML/CSS/JavaScript sem dependências.
- **Hospedagem de destino:** KingHost (Apache + PHP-FPM). Nada precisa ser instalado no servidor.
- **Idioma:** português brasileiro.

## Requisitos

| Onde | O que |
|---|---|
| Servidor | PHP 8.1 ou superior com `pdo_sqlite`, `mbstring`, `fileinfo`, `openssl`, `gd` e `zip`; recomendados: `sodium`, GD com WebP e Argon2id. SQLite 3.24 ou superior. Apache com `mod_rewrite`. |
| Desenvolvimento | Os mesmos requisitos acima, mais Git. Para os testes visuais: Node.js 22+ e Chrome ou Edge. |

## 1. Clonar

```bash
git clone <url-do-repositorio> teia-ade-sampa
cd teia-ade-sampa
```

## 2. Configurar o ambiente local

1. Instale o PHP 8.1+. No Windows: `winget install PHP.PHP.NTS.8.3`.
2. Confira se o PHP é encontrado: `bin/php.sh -v`.
   - O script localiza o PHP (variável `PHP_BIN`, `php` no PATH ou a instalação do winget).
   - Se o PHP não tiver `php.ini`, o script carrega `bin/php-dev.ini`, que ativa as extensões necessárias.
3. Os dados locais ficam em `../teia-ade-sampa-data`, uma pasta ao lado do projeto, fora do Git. Para usar outra pasta, defina `TEIA_DATA_DIR`.

## 3. Iniciar o servidor local

```bash
bin/serve.sh          # http://localhost:8080
bin/serve.sh 8000     # outra porta
```

Antes da instalação, o site já aparece com o conteúdo original do Figma. O painel leva ao instalador.

## 4. Instalar

1. Acesse `http://localhost:8080/instalar`.
2. Confira os requisitos e clique em **Preparar instalação**.
3. Abra o arquivo `install-token.txt` no diretório de dados e copie o código de 32 caracteres.
4. Preencha o token, o nome, o usuário e a senha (mínimo de 12 caracteres) e conclua.
5. Depois disso, `/instalar` responde 404 para sempre.

Detalhes em [docs/INSTALACAO.md](docs/INSTALACAO.md).

## 5. Acessar o painel

- Endereço: `/admin` (por exemplo, `http://localhost:8080/admin`).
- Manual para quem edita conteúdo, sem conhecimento técnico: [docs/CMS.md](docs/CMS.md).
- Perdeu o acesso de administrador? Use `/admin/recuperar`, com um token lido via FTP.

## 6. Executar os testes

```bash
bin/php.sh tests/run.php          # testes unitários e HTTP (sem PHPUnit)
bin/php.sh tests/run.php unit     # só os unitários
tests/visual/executar.sh          # comparação com o Figma, responsividade e acessibilidade (Node + Chrome)
```

O relatório visual é gerado em `tests/visual/reports/relatorio.html`. Veja [docs/TESTES.md](docs/TESTES.md).

## 7. Deploy na KingHost

Resumo:

1. Publique o repositório numa pasta vazia, por exemplo `www/teia`, pelo **Git Webhook** do painel.
2. O `.htaccess` da raiz encaminha tudo para `public/` e bloqueia o código-fonte.
3. Os dados vão automaticamente para `/home/<site>/teia-ade-sampa-data`, fora da pasta publicada.
4. Acesse `https://<domínio>/teia/instalar`.

Passo a passo completo e pontos que ainda precisam ser confirmados no servidor: [docs/DEPLOY.md](docs/DEPLOY.md).

## Documentação

| Documento | Conteúdo |
|---|---|
| [docs/ARQUITETURA.md](docs/ARQUITETURA.md) | Estrutura de pastas, fluxo das requisições e módulos |
| [docs/DESIGN_TOKENS.md](docs/DESIGN_TOKENS.md) | Cores, tipografia, espaçamentos e medidas extraídos do Figma |
| [docs/DECISOES_DE_DESIGN.md](docs/DECISOES_DE_DESIGN.md) | Decisões tomadas onde o Figma não tem especificação |
| [docs/INVENTARIO_FIGMA.md](docs/INVENTARIO_FIGMA.md) | Frames, componentes, textos e assets do TEIA no Figma |
| [docs/CMS.md](docs/CMS.md) | Manual do painel para quem edita conteúdo |
| [docs/PERMISSOES.md](docs/PERMISSOES.md) | Papéis, permissões por página e sessões |
| [docs/SEGURANCA.md](docs/SEGURANCA.md) | Controles de segurança (OWASP ASVS 2) |
| [docs/BANCO_DE_DADOS.md](docs/BANCO_DE_DADOS.md) | Tabelas, migrações e transações |
| [docs/INSTALACAO.md](docs/INSTALACAO.md) | Instalação e recuperação de acesso |
| [docs/DEPLOY.md](docs/DEPLOY.md) | Publicação na KingHost |
| [docs/MANUTENCAO.md](docs/MANUTENCAO.md) | Backups, restauração, logs e atualizações |
| [docs/TESTES.md](docs/TESTES.md) | Como testar e últimos resultados |
| [docs/PENDENCIAS_DE_CONTEUDO.md](docs/PENDENCIAS_DE_CONTEUDO.md) | Conteúdos e decisões pendentes |

## Links pendentes (TODO-LINK)

O cabeçalho e o rodapé reproduzem o Figma institucional. Os links para as outras áreas da ADE SAMPA (Home, Sobre, Programas, redes sociais etc.) estão **vazios de propósito**, porque esses endereços ainda não existem. Eles aparecem no site sem levar a lugar nenhum e devem ser preenchidos pelo painel, em **Cabeçalho e rodapé**. No código, procure por `TODO-LINK` em `app/content/layout.php` e `app/content/teia.php`.
