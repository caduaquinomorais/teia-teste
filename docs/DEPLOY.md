# Deploy na KingHost

> **Situação:** o deploy real **ainda não foi feito nem validado**. O procedimento se baseia na documentação oficial da KingHost (wiki king.host, consultada em 12/11/2026). Os itens marcados como ⚠️ precisam ser confirmados no servidor.

Sem o CMS, o site não precisa de banco, instalação, diretório de dados, extensões PHP extras nem tarefas agendadas.

## Como funciona

```
branch de trabalho ──► Pull Request ──► GitHub Actions (testes) ──► merge no main ──► Git Webhook ──► KingHost
                                          ✘ falhou: merge bloqueado
```

- **`main` = produção.** Tudo que entra no `main` é publicado pela KingHost.
- **Ninguém faz push direto no `main`.** As mudanças entram por Pull Request.
- **O PR só pode ser mesclado com os testes passando** (workflow [.github/workflows/testes.yml](../.github/workflows/testes.yml), PHP 8.1 e 8.3).

## O que foi verificado na documentação da KingHost

| Item | Situação | Fonte |
|---|---|---|
| Pasta pública da conta | ✅ `/home/<site>/www` | king.host/wiki/artigo/caminho-fisico-linux/ |
| `php_value`/`php_flag` no `.htaccess` | ❌ causam erro 500 (PHP-FPM); o projeto não usa | king.host/wiki/artigo/erro-500-causas-e-correcoes/ |
| Versão do PHP | ⚠️ escolhida no painel ("Configuração PHP"). A wiki lista só até a 7.3 (lista possivelmente desatualizada). **O projeto exige 8.1+.** | king.host/wiki/artigo/como-alterar-versao-php-do-meu-site/ |
| Deploy por Git | ✅ **Git Webhook** (GitHub/GitLab/Bitbucket), com branch e "Diretório para Publicação", que **precisa estar vazio** na primeira clonagem | king.host/wiki/artigo/como-integrar-github-ao-painel-kinghost/ |
| HTTPS | ✅ Let's Encrypt grátis no painel; forçar via `.htaccess` | king.host/wiki/artigo/instalar-certificado-ssl-lets-encrypt/ · king.host/wiki/artigo/forcar-utilizacao-de-https-via-htaccess/ |
| Servidor web | ⚠️ a plataforma tem Apache **e** Nginx (com Varnish). O `.htaccess` só funciona no **Apache**: confirme no painel | king.host/wiki/artigo/https-no-nginx/ |
| Site em subpasta (`/teia`) | ⚠️ não documentado; o projeto detecta o base path sozinho | — |

## Estrutura no servidor

**Opção A — subdomínio (preferível, se o painel permitir).** Ex.: `teia.adesampa.com.br`, com a raiz do subdomínio apontando para `.../teia/public`. Só a pasta `public/` fica acessível pela web; o `.htaccess` da raiz nem é usado.

**Opção B — subpasta do site principal.**

```
/home/<site>/www/
├── (outros sites/projetos da ADE SAMPA)
└── teia/                  ← "Diretório para Publicação" do Git Webhook (vazio na 1ª vez)
    ├── .git/              ← criado pelo Webhook; bloqueado pelo .htaccess
    ├── .htaccess          ← encaminha para public/ e bloqueia o código
    ├── public/
    └── app/ …
```

O site fica em `https://<domínio>/teia/`.

## Configuração única no GitHub

1. **Repositório privado:** *Settings → General → Danger Zone → Change visibility* (se ainda for público).
2. **Rodar o workflow uma vez:** depois do primeiro push com `.github/workflows/testes.yml`, confira em *Actions* que o "Testes" ficou verde. (O GitHub só oferece a verificação como obrigatória depois que ela rodou pelo menos uma vez.)
3. **Proteger o `main`:** *Settings → Branches → Add branch ruleset* (ou *Add classic branch protection rule*):
   - Branch: `main`.
   - ✅ **Require a pull request before merging** (aprovações: 0 se você trabalha sozinho; 1 se houver revisor).
   - ✅ **Require status checks to pass** → adicione **`PHP 8.1`** e **`PHP 8.3`**; marque *Require branches to be up to date*.
   - ✅ **Block force pushes** e não permitir exclusão do branch.
   - Em ruleset, deixe a *Bypass list* vazia (nem o administrador pula a regra).

## Primeiro deploy na KingHost

1. **PHP:** painel → *Configuração PHP* → **8.3** (mínimo 8.1).
2. **Servidor web:** confirme que o site usa **Apache**. Se for Nginx, veja a seção no fim deste arquivo antes de continuar.
3. **Pasta / subdomínio:** crie a pasta vazia `www/teia` (opção B) ou o subdomínio (opção A).
4. **Git Webhook:** painel → *Git Webhook*:
   - conecte a conta do GitHub e autorize o acesso ao repositório privado;
   - repositório: este; branch: **`main`**;
   - **Diretório para Publicação:** `www/teia` (vazio).
   - Salve e faça a primeira publicação (botão do painel ou um merge no `main`).
5. **HTTPS:** painel → *Certificado SSL* → ative o **Let's Encrypt** para o domínio/subdomínio. Quando `https://` abrir sem aviso, faça um PR descomentando o bloco "Força HTTPS" em `public/.htaccess`.
6. **Conferência** (troque `<url>` por `https://<domínio>/teia` ou pelo subdomínio):
   - [ ] `<url>/` abre o site, com fontes e imagens.
   - [ ] `<url>/app/content/teia.php` responde **404**. Se mostrar código ou um erro do PHP, o `.htaccess` não está ativo (Nginx?): **tire o site do ar e contate o suporte**.
   - [ ] `<url>/.git/config` responde **404**. Se baixar um arquivo, o código-fonte inteiro está exposto: **tire do ar**.
   - [ ] `<url>/docs/DEPLOY.md` e `<url>/tests/run.php` respondem **404**.
   - [ ] `<url>/nao-existe` mostra a página 404 do Teia.
   - [ ] `http://…` redireciona para `https://…` (depois do passo 5).
   - [ ] Os botões "Faça seu agendamento" levam a `https://app.adesampa.com.br/welcome`.
   - [ ] O mapa carrega.

## Atualizações (dia a dia)

```bash
git switch main && git pull
git switch -c ajuste/descricao-curta      # branch de trabalho
# … edite (ver CONTEUDO.md) e rode os testes: bin/php.sh tests/run.php
git add -A && git commit -m "conteudo: descreve a mudanca"
git push -u origin ajuste/descricao-curta
```

No GitHub, abra o Pull Request para o `main`, espere os testes ficarem verdes e clique em **Merge**. O Git Webhook publica o `main` na KingHost. Confira o site em seguida.

## Desfazer uma publicação (rollback)

No GitHub, abra o PR que causou o problema e clique em **Revert**. Isso cria um novo PR que desfaz a mudança; mescle-o e o Webhook publica a versão anterior. Pelo terminal:

```bash
git switch -c reverte/problema origin/main
git revert <hash-do-commit-de-merge> -m 1
git push -u origin reverte/problema       # e abra o PR
```

Não use `git push --force` no `main`: o histórico do servidor deixaria de bater com o do GitHub.

## Se o `.htaccess` da raiz for ignorado (observado em oranilom.com.br/teia, 16/11/2026)

Sintomas: `/teia/` dá **403 Forbidden**, `/teia/README.md` é servido e `/teia/public/` abre sem CSS. O `.htaccess` de `public/`, `app/`, `docs/` etc. funciona; só o da raiz de `/teia` não age.

O projeto já contorna isso sozinho: o [index.php](../index.php) da raiz é aberto pelo Apache em `/teia/`, e o app passa a apontar CSS, fontes e imagens para `/teia/public/assets/…`. O site funciona por completo em `/teia/`. Limitações enquanto o `.htaccess` da raiz estiver inativo:

- endereços inexistentes (`/teia/qualquer-coisa`) mostram o 404 da hospedagem, não o do Teia;
- arquivos soltos na raiz (`README.md`) ficam acessíveis. Não há nada sensível neles, e `app/`, `bin/`, `docs/` e `tests/` continuam bloqueados pelos próprios `.htaccess`.

Para resolver de vez: no Gerenciador de Arquivos/FTP da KingHost, confira se `www/teia/.htaccess` existe e tem o conteúdo do repositório; se existir, pergunte ao suporte por que ele não é aplicado (AllowOverride). Quando ele voltar a agir, nada precisa mudar no código.

## Se o servidor for Nginx (sem `.htaccess`)

Os arquivos de `app/` encerram sem executar nada se forem chamados diretamente, mas as URLs não funcionam sem reescrita, e `.git/`, `docs/` e `tests/` ficariam acessíveis. Peça ao suporte da KingHost para:

- apontar o document root para `www/teia/public` (resolve tudo); ou
- configurar a reescrita para `public/index.php` e negar acesso a `app/`, `bin/`, `docs/`, `tests/` e arquivos ocultos.

## Base path

O sistema detecta `/teia` automaticamente. Se um proxy mudar os caminhos, crie `app/config/app.local.php` no servidor com:

```php
<?php return ['base_path' => '/teia'];
```

Esse arquivo fica fora do Git. Se o Git Webhook o apagar num deploy, recrie.
