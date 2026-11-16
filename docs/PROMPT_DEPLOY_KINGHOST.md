# Prompt: deploy automático de site PHP na KingHost

Prompt para um agente de IA com acesso ao repositório (ex.: Claude Code, aberto na raiz do projeto). Ele reproduz o resultado em produção desde 16/11/2026: testes em todo Pull Request e publicação automática por FTPS depois do merge, com conferência do site no ar.

O texto incorpora o que foi descoberto na prática entre 13 e 16/11/2026, para que o agente não repita as tentativas que falharam (Git Webhook da KingHost, certificado incompleto, caminho absoluto no FTP, bloqueio de IP). A implementação de referência é [.github/workflows/testes.yml](../.github/workflows/testes.yml); a documentação resultante é [DEPLOY.md](DEPLOY.md).

**Como usar:** ajuste a tabela "Parâmetros" se for outro projeto, outra pasta ou outra conta. Depois copie tudo entre as linhas `--- INÍCIO DO PROMPT ---` e `--- FIM DO PROMPT ---`.

---

`--- INÍCIO DO PROMPT ---`

Você é um engenheiro de DevOps sênior. Configure o deploy automático deste repositório para a hospedagem compartilhada **KingHost**, seguindo as decisões e restrições abaixo. Elas vieram de testes reais nesse servidor. **Não reabra as decisões e não teste as alternativas descartadas.**

## Parâmetros

| Parâmetro | Valor |
|---|---|
| Repositório | `github.com/caduaquinomorais/teia-teste` (público) |
| Branch de produção | `main` |
| URL pública | `https://oranilom.com.br/teia/` |
| Domínio na KingHost | `oranilom.com.br` |
| Servidor FTP real | `web1160.kinghost.net` (`ftp.oranilom.com.br` é um CNAME para ele). Confirme com `nslookup ftp.<domínio>` |
| Pasta de destino | `teia`, dentro de `www` → no FTP: **`./teia/`** |
| Arquivos publicados | `app/`, `public/`, `index.php`, `.htaccess` |
| Arquivos que nunca sobem | `.github/`, `bin/`, `docs/`, `tests/`, `*.md`, `.git*` |
| Testes | `php -l` em todos os `.php` + `php tests/run.php`, em PHP **8.1** (mínimo) e **8.3** (recomendado) |
| Usuário | fala português, tem pouca experiência com Git/GitHub e não tem SSH nem usuário FTP extra (é cobrado à parte) |

## Contexto do projeto

- PHP puro 8.1+, sem Composer, banco ou build. Front controller em `public/index.php`.
- `.htaccess` na raiz encaminha tudo para `public/` e bloqueia `app/`, `bin/`, `docs/`, `tests/` e arquivos ocultos. `app/`, `bin/`, `docs/` e `tests/` também têm `.htaccess` próprio que nega acesso.
- O app detecta o base path (`/teia`) pelo `SCRIPT_NAME`.
- Não use `php_value`/`php_flag` em `.htaccess`: no PHP-FPM da KingHost, isso causa erro 500.

## Decisões já tomadas

1. **Não use o Git Webhook do painel da KingHost.** Ele registrou os pushes, mas parou de atualizar os arquivos sem mostrar erro. A pasta criada por ele ficou com outro dono, invisível e inacessível ao FTP (`550 denied`), e com o `.htaccess` da raiz ignorado (site em 403). Ele também publicava `.git/`, `docs/` e `tests/` e não dependia dos testes.
2. **Use GitHub Actions + `SamKirkland/FTP-Deploy-Action@v4.3.5`, com FTPS.** Não há SSH/SFTP. Não use FTP sem criptografia.
3. **Fluxo com Pull Request:** branch → PR → testes → merge → testes → deploy. O deploy só roda no `main` (`push` ou `workflow_dispatch`), nunca em `pull_request`, e só depois de os testes passarem (`needs`).
4. **Repositório público.** No plano gratuito do GitHub, rulesets só são aplicados em repositórios públicos. Antes de recomendar isso, varra o histórico (`git log --all -p`) atrás de senhas, tokens, chaves, `.env`, bancos e backups, e informe o resultado.
5. **`index.php` de reserva na raiz do repositório.** Ele faz `require __DIR__ . '/public/index.php'`. Quando a requisição entra por ele (o `.htaccess` da raiz foi ignorado), o app aponta `/assets/…` para `<base>/public/assets/…`; a detecção compara `dirname(SCRIPT_FILENAME)` com a raiz do projeto. Cubra os dois cenários com testes.

## Fatos verificados do servidor (trate como requisitos)

1. **Certificado FTPS incompleto.** O servidor envia só o certificado final (`CN=*.kinghost.net`, emitido pela "GlobalSign GCC R46 AlphaSSL CA 2025"), sem o intermediário. O Windows completa a cadeia sozinho, mas o OpenSSL e o Node do runner Linux falham com `unable to get local issuer certificate`. Crie uma etapa que:
   - leia o certificado (`openssl s_client -connect $FTP_SERVER:21 -starttls ftp`);
   - extraia a URL "CA Issuers" do campo AIA, baixe o intermediário e converta de DER para PEM;
   - **valide** com `openssl verify -untrusted intermediario.pem servidor.pem`;
   - exporte `FTP_CA_BUNDLE` (CAs do sistema + intermediário) para o `curl` e `NODE_EXTRA_CA_CERTS` (intermediário) para a action.

   **Nunca desligue a verificação TLS** (nem `security: loose`, nem `curl -k`).
2. **Nome do servidor:** use `web1160.kinghost.net`. Com `ftp.<domínio>`, o certificado não confere.
3. **O FTP abre dentro de `www`, mas `www` não é a raiz `/`.** O `server-dir` da action precisa ser **relativo**: `./teia/`. Com `/teia/` absoluto, a action reporta sucesso, mas grava fora da `www`, e o site continua vazio. Para o `curl`, `ftp://host/teia/` já é relativo à pasta de login.
4. **Bloqueio de IP.** Depois de uma tentativa de login falha, ou de várias conexões seguidas, o servidor deixa de responder ao IP por alguns minutos (`ETIMEDOUT` na porta 21). Portanto:
   - normalize caminhos: um `FTP_DIR="./x/"` concatenado à URL vira `ftp://host./x/`, gera login anônimo, `530` e bloqueio;
   - faça poucas conexões na verificação;
   - pause cerca de 20 s antes do envio;
   - se o envio falhar (`continue-on-error` + `steps.<id>.outcome`), espere 60 s e tente uma segunda vez.
5. **Listagem:** use `LIST` (que inclui pastas) para diagnosticar, não `NLST`.

## O que entregar

### 1. Workflow `.github/workflows/testes.yml`

- Gatilhos: `pull_request`, `push` em `main` e `workflow_dispatch`. `permissions: contents: read`.
- Concurrency: cancela execuções antigas só em PR; no `main` nunca interrompe um deploy (grupo próprio, `deploy-producao`, com `cancel-in-progress: false`).
- **Job de testes** com matriz PHP `['8.1', '8.3']` e nome `PHP ${{ matrix.php }}`; esses nomes são os checks obrigatórios do ruleset. Use `shivammathur/setup-php@v2` com `mbstring, curl, openssl`, `zend.assertions=1` e `coverage: none`.
- **Job de deploy** (`needs: testes`, environment `producao` com a URL do site):
  - `env`: `FTP_SERVER` (padrão `web1160.kinghost.net`), `FTP_DIR` (vazio = descoberta automática), `FTP_PROTOCOL` (padrão `ftps`), todos sobrescrevíveis por `vars.*`.
  - Etapa de cadeia do certificado (fato 1).
  - **Etapa "Localizar a pasta do site"** (`id: destino`):
    - verifica se os segredos existem;
    - grava um `netrc` com `umask 077` e o apaga no `trap EXIT`; a senha nunca vai em linha de comando;
    - faz login e lista a raiz;
    - testa `FTP_DIR`, depois `/teia/`, `/www/teia/`, `/public_html/teia/` e `/`;
    - **só aceita a pasta que já contém `app` e `public`**, inclusive quando `FTP_DIR` estiver definida;
    - exporta `dir` em `GITHUB_OUTPUT`;
    - escreve o diagnóstico (login, raiz, cada tentativa com o erro do curl) como **anotações** `::notice::`/`::error::`, com quebras codificadas como `%0A`. Assim, o diagnóstico pode ser lido pela API pública (`/check-runs/<id>/annotations`), sem login e sem pedir ao usuário que copie logs.
  - Pausa, envio (`server-dir: .${{ steps.destino.outputs.dir }}`, `protocol: ${{ env.FTP_PROTOCOL }}` e a lista de exclusões) e segunda tentativa condicionada.
  - **Conferência final:** `GET` na URL pública precisa responder 200, e o `site.css` referenciado no HTML também.
- Comente no topo do arquivo quais segredos e variáveis existem e onde ficam.

### 2. Documentação `docs/DEPLOY.md`

Descreva o estado atual (não um plano):

- visão geral e etapas do deploy;
- segredos e variáveis (em qual aba, e que senha **nunca** vai em *Variables*);
- ruleset do `main`;
- estrutura no servidor;
- rotina diária e rollback via **Revert**;
- checklist de conferência;
- tabela **sintoma → causa → solução** com os fatos acima;
- histórico de por que o webhook foi descartado;
- pendências.

Atualize também o README e qualquer documento que ainda cite o webhook.

### 3. Validação antes de entregar

- Rode a suíte de testes localmente.
- Extraia o script da etapa "Localizar a pasta" e execute-o com `curl` simulado (uma função bash), cobrindo: raiz com só `.htaccess` + `teia/` com `app` e `public`; `FTP_DIR` vazio, `/`, `./teia/` e `./www/teia/`; senha errada (`530`). Confirme que nenhuma URL malformada é gerada e que `/` sem `app`/`public` é recusado.
- Simule o servidor sem `.htaccess` (`php -S 127.0.0.1:PORTA -t .` na raiz) e confirme que a página e todos os assets respondem 200.

## Divisão de trabalho com o usuário

Faça você mesmo tudo o que não exige credenciais: código, testes, commits em **branch** (nunca direto no `main`), push e acompanhamento das execuções pela API pública do GitHub. Peça ao usuário só o que exige login ou senha, **um passo por vez**, com **links diretos**:

1. Segredos: `https://github.com/<repo>/settings/secrets/actions/new`, com `FTP_USERNAME` e `FTP_PASSWORD` **na aba Secrets**.
2. PR: `https://github.com/<repo>/compare/main...<branch>?expand=1` → *Create pull request* → esperar os ✅ → *Merge*.
3. Ruleset do `main` (Active, bypass vazio; exigir PR e os checks `PHP 8.1` e `PHP 8.3`; bloquear force push e exclusão).
4. Remover o Git Webhook (painel KingHost e GitHub → *Settings → Webhooks*).
5. Se a pasta de destino tiver sido criada pelo webhook, apagá-la e criar `teia/app` e `teia/public` pelo FTP (ou pelo gerenciador de arquivos do painel).

Depois de cada merge, acompanhe a execução até o fim, leia as anotações e confira o site com `curl`:

- página e todos os assets referenciados respondem 200;
- `/app/…`, `/app/config/app.php`, `/.htaccess` → 403/404;
- `/docs/…`, `/tests/…`, `/.git/config`, `/.ftp-deploy-sync-state.json` → 404;
- uma URL inexistente mostra a página 404 do site;
- cabeçalhos CSP, HSTS e X-Frame-Options presentes.

## Regras de segurança

- Nunca peça a senha no chat. Se ela aparecer (por exemplo, num print ou numa variável comum), oriente a apagar e **trocar a senha**.
- Nunca desligue a verificação de certificado.
- Nunca publique numa pasta que não tenha a assinatura do projeto (`app/` + `public/`).
- Nunca use `git push --force` no `main`, nem envio manual por FTP numa pasta controlada pelo webhook.
- Antes de qualquer ação irreversível ou visível para outras pessoas, confirme com o usuário.

## Critério de pronto

Um merge no `main` dispara testes e deploy, e todos os jobs ficam verdes. A conferência final do workflow passa. A verificação manual acima passa. `docs/DEPLOY.md` descreve o que está no ar.

`--- FIM DO PROMPT ---`

---

## Adaptar para outro projeto ou subdomínio

- **Outro domínio ou conta:** troque o servidor (`nslookup ftp.<domínio>` mostra o `webNNNN.kinghost.net`) e a URL pública.
- **Outra pasta** (ex.: homologação em `www/homolog`): troque `teia` por `homolog` nos candidatos da verificação e na URL da conferência. O destino continua relativo (`./homolog/`). Lembre-se de que a pasta precisa existir com `app/` e `public/` antes do primeiro deploy.
- **Site com build (Astro, Vite etc.):** acrescente o build antes do envio e use `local-dir: ./dist/`. Nesse caso, a assinatura da pasta deixa de ser `app/` + `public/`; escolha um arquivo do build (ex.: `index.html`) e crie a pasta com ele antes do primeiro deploy.
