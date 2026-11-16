# Deploy na KingHost

> **Situação:** o deploy real **ainda não foi feito nem validado**. O procedimento se baseia na documentação oficial da KingHost (wiki king.host, consultada em 12/11/2026). Os itens marcados como ⚠️ precisam ser confirmados no servidor.

Sem o CMS, o site não precisa de banco, instalação, diretório de dados, extensões PHP extras nem tarefas agendadas.

## Como funciona

```
branch de trabalho ──► Pull Request ──► testes ──► merge no main ──► testes ──► deploy por FTP ──► KingHost
                                        ✘ merge bloqueado               ✘ nada é publicado
```

- **`main` = produção.** Tudo que entra no `main` é publicado.
- **Ninguém faz push direto no `main`.** As mudanças entram por Pull Request.
- **O PR só pode ser mesclado com os testes passando** (workflow [.github/workflows/testes.yml](../.github/workflows/testes.yml), PHP 8.1 e 8.3).
- **O deploy é feito pelo GitHub Actions, por FTP**, só depois de os testes passarem no `main`. Envia apenas `app/`, `public/`, `index.php` e `.htaccess`. Ao final, confere se `/teia/` abre com o CSS.
- **O Git Webhook da KingHost não é usado.** Ele recebia os avisos do GitHub, mas parou de atualizar os arquivos depois de 13/11/2026 sem mostrar erro.

## Configurar o deploy por FTP (uma vez)

1. **Segredos no GitHub:** *Settings → Secrets and variables → Actions → New repository secret*. Crie os dois:
   - `FTP_USERNAME`
   - `FTP_PASSWORD`

   Use os dados de FTP do painel da KingHost. Eles ficam criptografados e não aparecem nos logs nem para quem vê o repositório.

   O servidor FTP da KingHost **não envia o certificado intermediário** da GlobalSign. O Windows completa a cadeia sozinho; o Linux do GitHub não, e recusa a conexão com `unable to get local issuer certificate`. O workflow baixa o intermediário do endereço oficial que consta no próprio certificado, valida a cadeia e segue com a verificação TLS ligada.

   O servidor já vem configurado: **`web1160.kinghost.net`**, com FTPS. `ftp.oranilom.com.br` é um apelido desse servidor, mas o certificado TLS da KingHost só vale para `*.kinghost.net`, então usar o apelido faria a conexão segura falhar. Se a KingHost mudar a conta de servidor, crie a variável `FTP_SERVER` com o novo nome (descubra com `nslookup ftp.oranilom.com.br`).
2. **Pasta de destino:** descoberta automaticamente. O deploy testa `FTP_DIR` (se existir) e depois `/teia/`, `/www/teia/`, `/public_html/teia/` e `/`, e só aceita uma pasta que **já tenha `app/` e `public/`** deste projeto, para nunca publicar por cima de outro site. Na KingHost, o FTP já começa dentro de `www`, então a pasta é `/teia/`. O resultado aparece como anotação no resumo da execução, em *Actions*.
3. **Desligar o webhook antigo:** no painel da KingHost, em *Git Webhook*, remova a integração. No GitHub, em *Settings → Webhooks*, apague o webhook da KingHost. Assim fica uma única forma de publicar.
4. **Primeiro deploy:** aba *Actions* → workflow **Testes** → **Run workflow** (branch `main`). O primeiro envio manda todos os arquivos; os seguintes, só o que mudou.
5. Se o passo "Enviar para a KingHost por FTP" falhar com erro de conexão ou TLS, crie a variável `FTP_PROTOCOL` = `ftp` e rode de novo.

Depois de um deploy bem-sucedido, a pasta `.git/` e as pastas `docs/`, `tests/` e `bin/` que o webhook antigo deixou em `www/teia` podem ser apagadas pelo FTP. Elas não são mais usadas.

## O que foi verificado na documentação da KingHost

| Item | Situação | Fonte |
|---|---|---|
| Pasta pública da conta | ✅ `/home/<site>/www` | king.host/wiki/artigo/caminho-fisico-linux/ |
| `php_value`/`php_flag` no `.htaccess` | ❌ causam erro 500 (PHP-FPM); o projeto não usa | king.host/wiki/artigo/erro-500-causas-e-correcoes/ |
| Versão do PHP | ⚠️ escolhida no painel ("Configuração PHP"). A wiki lista só até a 7.3 (lista possivelmente desatualizada). **O projeto exige 8.1+.** | king.host/wiki/artigo/como-alterar-versao-php-do-meu-site/ |
| Deploy por Git | ⚠️ existe o **Git Webhook**, mas na prática parou de atualizar os arquivos após 13/11/2026. **Não usado:** o deploy é por FTP via GitHub Actions | king.host/wiki/artigo/como-integrar-github-ao-painel-kinghost/ |
| HTTPS | ✅ Let's Encrypt grátis no painel; forçar via `.htaccess` | king.host/wiki/artigo/instalar-certificado-ssl-lets-encrypt/ · king.host/wiki/artigo/forcar-utilizacao-de-https-via-htaccess/ |
| Servidor web | ⚠️ a plataforma tem Apache **e** Nginx (com Varnish). O `.htaccess` só funciona no **Apache**: confirme no painel | king.host/wiki/artigo/https-no-nginx/ |
| Site em subpasta (`/teia`) | ⚠️ não documentado; o projeto detecta o base path sozinho | — |

## Estrutura no servidor

**Opção A — subdomínio (preferível, se o painel permitir).** Ex.: `teia.adesampa.com.br`, com a raiz do subdomínio apontando para `.../teia/public`. Só a pasta `public/` fica acessível pela web; o `.htaccess` da raiz nem é usado.

**Opção B — subpasta do site principal.**

```
/home/<site>/www/
├── (outros sites/projetos da ADE SAMPA)
└── teia/                  ← destino do deploy por FTP (FTP_DIR)
    ├── .htaccess          ← encaminha para public/ e bloqueia o código
    ├── index.php          ← reserva, se o .htaccess acima for ignorado
    ├── public/
    └── app/ …
```

O site fica em `https://<domínio>/teia/`.

## Configuração única no GitHub

1. **Visibilidade:** o repositório é **público** (não há segredos no código; no plano gratuito, a proteção de branch só vale em repositórios públicos). As senhas de FTP ficam nos *Secrets*, nunca no código.
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
3. **Pasta / subdomínio:** crie a pasta `www/teia` (opção B) ou o subdomínio (opção A).
4. **Deploy:** configure o FTP conforme a seção "Configurar o deploy por FTP" acima e rode o workflow.
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

No GitHub, abra o Pull Request para o `main`, espere os testes ficarem verdes e clique em **Merge**. O GitHub Actions roda os testes de novo e publica por FTP (acompanhe na aba *Actions*; o job "Deploy KingHost" fica verde quando o site está no ar).

## Desfazer uma publicação (rollback)

No GitHub, abra o PR que causou o problema e clique em **Revert**. Isso cria um novo PR que desfaz a mudança; mescle-o e o deploy publica a versão anterior. Pelo terminal:

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

Esse arquivo fica fora do Git e o deploy por FTP não o apaga (só remove arquivos que ele mesmo enviou).
