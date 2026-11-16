# Deploy na KingHost

> **Situação (16/11/2026):** em produção em **https://oranilom.com.br/teia/**, publicado e conferido pelo próprio pipeline (execução "Merge pull request #6"). Verificação manual no mesmo dia: página e os 28 arquivos que ela carrega respondem 200; `app/`, configurações, `docs/`, `tests/`, `.git` e `.htaccess` bloqueados (403/404); 404 personalizado ativo; cabeçalhos CSP, HSTS e X-Frame-Options presentes.

O site não precisa de banco, instalação, diretório de dados, extensões PHP extras nem tarefas agendadas. Publicar é só copiar arquivos.

## Visão geral

```
branch ──► Pull Request ──► testes (PHP 8.1 e 8.3) ──► merge no main ──► testes ──► deploy FTPS ──► confere o site
                            ✘ merge bloqueado                                       ✘ nada é publicado
```

| Peça | Onde | O que faz |
|---|---|---|
| Testes | job `PHP 8.1` / `PHP 8.3` em [.github/workflows/testes.yml](../.github/workflows/testes.yml) | `php -l` em todos os arquivos + `php tests/run.php` (unitários e HTTP). Rodam em todo PR e todo push no `main`. |
| Deploy | job `Deploy KingHost (FTP)`, no mesmo arquivo | Só no `main` (push ou botão *Run workflow*) e só se os testes passarem. Envia por **FTPS** para `www/teia` e confere se o site responde com o CSS. |
| Hospedagem | KingHost, servidor `web1160.kinghost.net`, Apache + PHP-FPM | Serve `www/teia`. O `.htaccess` da raiz encaminha tudo para `public/` e bloqueia o código. |

**Não é usado:** o *Git Webhook* do painel da KingHost (ver [Histórico](#histórico-por-que-não-o-git-webhook)).

## O que o deploy faz, passo a passo

1. **Completa a cadeia do certificado FTPS.** O FTP da KingHost envia só o próprio certificado, sem o intermediário da GlobalSign. O workflow baixa o intermediário do endereço oficial gravado no certificado (campo AIA), valida a cadeia contra as autoridades do sistema e entrega o resultado ao `curl` e ao Node (`NODE_EXTRA_CA_CERTS`). A verificação TLS **continua ligada**.
2. **Localiza a pasta do site.** Faz login, lista a raiz e testa `FTP_DIR` (se existir), `/teia/`, `/www/teia/`, `/public_html/teia/` e `/`. **Só aceita uma pasta que já tenha `app/` e `public/`**, para nunca publicar por cima de outro site. O resultado aparece como anotação no resumo da execução (dá para ler sem abrir o log).
3. **Pausa 20 s** (a KingHost limita conexões seguidas do mesmo IP).
4. **Envia** com `SamKirkland/FTP-Deploy-Action@v4.3.5` para **`./teia/`** (caminho relativo, ver abaixo). Envia só `app/`, `public/`, `index.php` e `.htaccess`; ignora `.github/`, `bin/`, `docs/`, `tests/`, `*.md` e arquivos do Git. Só transfere o que mudou (guarda o estado em `.ftp-deploy-sync-state.json` no servidor) e apaga do servidor apenas arquivos que ele mesmo enviou.
5. **Se a conexão cair**, espera 1 minuto e tenta de novo, uma vez.
6. **Confere o site:** `https://oranilom.com.br/teia/` precisa responder 200 e o CSS referenciado na página também.

## Configuração

### Segredos (aba *Secrets*): obrigatórios

*Settings → Secrets and variables → Actions → **Secrets** → New repository secret*

| Nome | Valor |
|---|---|
| `FTP_USERNAME` | usuário FTP principal da conta (`oranilom`) |
| `FTP_PASSWORD` | senha desse usuário |

> ⚠️ **Senha só em *Secrets*.** Na aba *Variables* o valor fica visível em texto puro. Se isso acontecer, apague a variável e **troque a senha** no painel da KingHost.

### Variáveis (aba *Variables*): opcionais, normalmente **não devem existir**

| Nome | Padrão | Quando criar |
|---|---|---|
| `FTP_SERVER` | `web1160.kinghost.net` | Se a KingHost mover a conta. Descubra o novo nome com `nslookup ftp.oranilom.com.br` (é um CNAME). **Não use `ftp.oranilom.com.br`**: o certificado só vale para `*.kinghost.net`. |
| `FTP_DIR` | descoberta automática | Só se a pasta mudar de nome. Escreva relativo à pasta onde o FTP abre (ex.: `teia/`). |
| `FTP_PROTOCOL` | `ftps` | Nunca em uso normal. `ftp` manda a senha sem criptografia. |

### Ambiente `producao`

Criado automaticamente pelo job de deploy. O link do ambiente aponta para o site.

### Proteção do `main`

*Settings → Rules → Rulesets → New branch ruleset*:

- **Enforcement status:** Active; **Bypass list:** vazia; **Target:** default branch.
- ✅ Restrict deletions · ✅ Block force pushes
- ✅ Require a pull request before merging (aprovações: 0 se houver uma pessoa só)
- ✅ Require status checks to pass → `PHP 8.1` e `PHP 8.3` · ✅ Require branches to be up to date

O repositório é **público**. No plano gratuito, rulesets só são aplicados em repositórios públicos, e o código não tem segredos (histórico verificado em 16/11/2026).

## Estrutura no servidor

O FTP da conta **abre dentro de `www`**, mas `www` **não é a raiz `/`** do FTP. Por isso o destino é `./teia/` (relativo). `/teia/` (absoluto) aponta para **fora** da `www`, e o site não enxerga os arquivos.

```
/home/<conta>/www/          ← onde o FTP abre (aparece como "pasta atual", não como "/")
├── .htaccess               (do site principal)
└── teia/                   ← destino do deploy (./teia/)
    ├── .htaccess           encaminha tudo para public/ e bloqueia app/, bin/, docs/, tests/ e ocultos
    ├── index.php           reserva: só é usado se o .htaccess acima for ignorado
    ├── app/                código e conteúdo (bloqueado por app/.htaccess)
    ├── public/             front controller + assets
    └── .ftp-deploy-sync-state.json   estado do deploy (não é servido)
```

O site detecta o caminho `/teia` sozinho (base path pelo `SCRIPT_NAME`). Se um proxy mudar os caminhos, crie no servidor `app/config/app.local.php` com `<?php return ['base_path' => '/teia'];`. O arquivo fica fora do Git, e o deploy não o apaga.

## Dia a dia

```bash
git switch main && git pull
git switch -c ajuste/descricao-curta
# edite (ver CONTEUDO.md) e rode os testes: bin/php.sh tests/run.php
git add -A && git commit -m "conteudo: descreve a mudanca"
git push -u origin ajuste/descricao-curta
```

No GitHub: **Compare & pull request → Create pull request** → esperar ✅ em `PHP 8.1` e `PHP 8.3` → **Merge pull request → Confirm merge**. Acompanhe em *Actions*: o deploy leva cerca de 1 minuto, e o job **Deploy KingHost (FTP)** fica verde só depois de conferir o site no ar.

**Publicar de novo sem mudar código:** *Actions → Testes → Run workflow → branch `main`*.

### Desfazer (rollback)

No PR que causou o problema, clique em **Revert**, depois mescle o PR de reversão; o deploy publica a versão anterior. Pelo terminal:

```bash
git switch -c reverte/problema origin/main
git revert <hash-do-merge> -m 1
git push -u origin reverte/problema       # e abra o PR
```

Não use `git push --force` no `main`.

## Conferência após mudanças de infraestrutura

O deploy já confere o essencial. Depois de mexer em `.htaccess`, servidor ou pasta, confira também:

- [ ] `/teia/` abre com fontes e imagens, e o CSS vem de `/teia/assets/…` (se vier de `/teia/public/assets/…`, o `.htaccess` da raiz está sendo ignorado; ver diagnóstico).
- [ ] `/teia/app/content/teia.php`, `/teia/app/config/app.php` e `/teia/.htaccess` → **403/404**.
- [ ] `/teia/docs/DEPLOY.md`, `/teia/tests/run.php`, `/teia/.git/config` e `/teia/.ftp-deploy-sync-state.json` → **404**.
- [ ] `/teia/nao-existe` → página 404 do Teia.
- [ ] Os botões "Faça seu agendamento" levam a `https://app.adesampa.com.br/welcome`, e o mapa carrega.

## Diagnóstico: sintoma → causa → solução

Todos estes casos aconteceram de verdade em 13 a 16/11/2026, e o workflow atual já trata os que dependem dele.

| Sintoma | Causa | Solução |
|---|---|---|
| `curl: (60) … unable to get local issuer certificate` | O FTP da KingHost não envia o certificado intermediário. Funciona no Windows, que busca o intermediário sozinho, e falha no Linux. | Etapa "Completar a cadeia do certificado FTPS". **Não** desligue a verificação TLS. |
| Erro de certificado com `ftp.oranilom.com.br` | O certificado é `*.kinghost.net`. | Use `web1160.kinghost.net` (padrão do workflow). |
| `curl: (9) Server denied you to change to the given directory` e a raiz do FTP mostra só `.htaccess` | A pasta foi criada pelo **Git Webhook** da KingHost, com outro dono, e fica invisível e inacessível ao usuário FTP. | Apagar a pasta antiga e recriá-la pelo FTP (com `app/` e `public/`) ou deixar o deploy criar. |
| Deploy verde no envio, mas o site continua vazio ou 403 | `server-dir` absoluto (`/teia/`) aponta para fora da `www`. | Usar caminho relativo `./teia/` (já é o padrão). |
| `ETIMEDOUT … 191.6.213.70:21` logo depois de uma conexão que funcionou | A KingHost bloqueia o IP por alguns minutos após uma tentativa de login falha (ex.: login anônimo por URL malformada `host./…`) ou após muitas conexões seguidas. | Caminhos normalizados, pausa de 20 s e segunda tentativa após 60 s (já no workflow). |
| `Login incorrect` / `530` / `curl: (67)` | Usuário ou senha errados, ou senha cadastrada em *Variables* em vez de *Secrets*. | Corrigir em *Secrets*. Depois de erros repetidos, espere alguns minutos antes de tentar de novo (bloqueio de IP). |
| Variáveis criadas, mas o log continua igual | Foram criadas na aba errada (*Secrets* ou *Environment*). | Criar em *Variables → Repository variables* e usar **Re-run all jobs**. |
| `/teia/` dá **403 Forbidden** e `/teia/public/` abre **sem CSS** | O `.htaccess` da raiz de `/teia` não foi aplicado (aconteceu com a pasta criada pelo webhook). | O [index.php](../index.php) da raiz serve o site e aponta os assets para `/teia/public/assets/…`. Na pasta criada pelo FTP, o `.htaccess` voltou a funcionar. |
| Erro 500 depois de mexer no `.htaccess` | `php_value`/`php_flag` não são aceitos no PHP-FPM da KingHost. | Não usar essas diretivas. |

**Para ler o diagnóstico sem abrir o log:** o resumo de cada execução em *Actions* mostra as anotações "Login OK … Raiz do FTP: …" e "Pasta de destino: …". O repositório é público, então dá para consultar também pela API: `GET /repos/caduaquinomorais/teia-teste/check-runs/<job-id>/annotations`.

## Histórico: por que não o Git Webhook

O primeiro plano (12/11) era o *Git Webhook* do painel, que clona o repositório em `www/teia` a cada push. Na prática:

- publicou até 13/11 e depois **parou de atualizar os arquivos**, embora continuasse registrando os pushes no log do painel e o GitHub mostrasse as entregas com ✓;
- a pasta criada por ele ficou **inacessível ao FTP** e com o `.htaccess` da raiz **ignorado** (site em 403);
- publicava o repositório inteiro, incluindo `.git/`, `docs/` e `tests/`, e não dependia dos testes.

O deploy por GitHub Actions + FTPS resolve os três pontos e mostra erros claros. **Pendente:** remover a integração em *painel KingHost → Git Webhook* e o webhook em *GitHub → Settings → Webhooks*.

## KingHost: o que vale saber

| Item | Situação |
|---|---|
| PHP | 8.1+ obrigatório; recomendado 8.3 (painel → *Configuração PHP*). |
| `.htaccess` | Funciona (Apache). Sem `php_value`/`php_flag`. |
| HTTPS | Let's Encrypt pelo painel. `http://` já redireciona para `https://`. HSTS enviado pelo PHP. |
| FTP | `web1160.kinghost.net`, porta 21, FTPS explícito (AUTH TLS). Certificado `*.kinghost.net` sem intermediário na cadeia. Abre dentro de `www`. Bloqueio temporário de IP após falhas. |
| SFTP/SSH | Não utilizados. |

## Pendências

- [ ] Remover o Git Webhook (painel KingHost e GitHub).
- [ ] Trocar a senha FTP (ela apareceu em texto puro em 16/11) e atualizar o segredo `FTP_PASSWORD`.
- [ ] Apagar pelo FTP a pasta `teia` criada **fora** da `www` por um envio com caminho absoluto em 16/11 (não afeta o site).
- [ ] Confirmar que o ruleset do `main` está **Active**.
