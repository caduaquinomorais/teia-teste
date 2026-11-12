# Deploy na KingHost

> **Situação:** o deploy real **ainda não foi feito nem validado**. Este procedimento se baseia na documentação oficial da KingHost (wiki king.host, consultada em 12/11/2026) e em testes locais. Os itens marcados como ⚠️ **precisam ser confirmados no servidor** antes de declarar o site pronto para produção.

## O que foi verificado na documentação da KingHost

| Item | Situação | Fonte |
|---|---|---|
| Pasta pública da conta | ✅ `/home/<site>/www`; a raiz do FTP mostra `www` e pastas irmãs | king.host/wiki/artigo/caminho-fisico-linux/ |
| PHP roda com o mesmo usuário do FTP (php-fpm) | ✅ não exige permissão 777 | king.host/wiki/artigo/duvidas-gerais-sobre-hospedagem/ |
| `php.ini` editável | ❌ não, em hospedagem compartilhada | king.host/wiki/artigo/tecnologias-web-duvidas-frequentes/ |
| `php_value`/`php_flag` no `.htaccess` | ❌ **causam erro 500** (PHP-FPM); o projeto não usa | king.host/wiki/artigo/erro-500-causas-e-correcoes/ |
| Versão do PHP | ⚠️ escolhida no painel ("Configuração PHP"). A wiki lista só até a 7.3 (lista possivelmente desatualizada). **O projeto exige 8.1+.** | king.host/wiki/artigo/como-alterar-versao-php-do-meu-site/ |
| Extensões (pdo_sqlite, gd, zip, fileinfo, sodium…) | ⚠️ não documentadas; **o instalador verifica tudo** | — |
| Gravar fora do `www` (ex.: `/home/<site>/teia-ade-sampa-data`) | ⚠️ provável (mesmo usuário), mas não documentado; **o instalador faz teste real de escrita** | — |
| Deploy por Git | ✅ ferramenta **Git Webhook** (GitHub/GitLab/Bitbucket), com branch e "Diretório para Publicação", que **precisa estar vazio** na primeira clonagem | king.host/wiki/artigo/como-integrar-github-ao-painel-kinghost/ |
| O deploy apaga arquivos que não estão no Git? | ⚠️ não documentado; por isso os dados ficam **fora** da pasta publicada | — |
| Tarefas agendadas | ✅ "Cronjob" no painel, por **chamada HTTP** com o cabeçalho `X-Cron-Auth`; timeout de 60 s | king.host/wiki/artigo/como-configurar-cronjob/ |
| HTTPS | ✅ Let's Encrypt grátis no painel (exige DNS apontado para a KingHost); forçar HTTPS via `.htaccess` | king.host/wiki/artigo/instalar-certificado-ssl-lets-encrypt/ · king.host/wiki/artigo/forcar-utilizacao-de-https-via-htaccess/ |
| Servidor web | ⚠️ a plataforma tem Apache **e** Nginx (com Varnish). O `.htaccess` só funciona no **Apache**: confirme no painel | king.host/wiki/artigo/https-no-nginx/ |
| Site em subpasta (`/teia`) | ⚠️ não documentado; o projeto detecta o base path sozinho | — |

## Estrutura recomendada no servidor

```
/home/<site>/
├── www/
│   ├── (outros sites/projetos da ADE SAMPA)
│   └── teia/                  ← "Diretório para Publicação" do Git Webhook (vazio na 1ª vez)
│       ├── .htaccess          ← encaminha para public/ e bloqueia o código
│       ├── public/
│       └── app/ …
└── teia-ade-sampa-data/       ← criado pelo instalador (fora do www, fora do deploy)
```

O site fica em `https://<domínio>/teia/`. Se o painel permitir apontar um domínio ou subdomínio direto para `www/teia/public`, melhor ainda: aí o `.htaccess` da raiz nem é usado.

## Primeiro deploy

1. **Repositório:** suba o projeto para o GitHub, num repositório **privado**. O `.gitignore` já impede banco, uploads, logs, chaves e configurações locais.
2. **PHP:** no painel KingHost → *Configuração PHP*, escolha **PHP 8.1 ou superior** (de preferência 8.3).
3. **Git Webhook:** no painel → *Git Webhook*:
   - conecte o GitHub;
   - escolha o repositório e o branch `main`;
   - defina o **Diretório para Publicação** como `www/teia` (pasta vazia).
4. **HTTPS:** ative o Let's Encrypt em *Certificado SSL*. Depois, em `public/.htaccess`, descomente o bloco "Força HTTPS".
5. **Instalação:** acesse `https://<domínio>/teia/instalar`.
   - Confira os requisitos: todos devem estar OK, principalmente "Diretório de dados fora da área pública" e "gravável".
   - Clique em *Preparar instalação*.
   - Pelo FTP, abra `/teia-ade-sampa-data/install-token.txt` (na raiz da conta, ao lado de `www`).
   - Conclua criando o administrador.
6. **Forçar HTTPS no PHP:** pelo FTP, crie `/teia-ade-sampa-data/config.local.php` com:
   ```php
   <?php
   return [
       'force_https' => true,
   ];
   ```
   Isso garante cookies `Secure` e HSTS mesmo atrás do Varnish.
7. **Cronjob de backup (opcional, recomendado):** no painel KingHost → *Cronjob*:
   - crie uma tarefa **diária** (ex.: 03:00) chamando `https://<domínio>/teia/cron/backup`;
   - copie o código de autenticação mostrado pela KingHost;
   - cole esse código em **Painel do Teia → Backups → Configurações**.
8. **Conferência final:**
   - [ ] `https://<domínio>/teia/` abre o site.
   - [ ] `https://<domínio>/teia/app/bootstrap.php` responde **404**. Se mostrar código ou um erro do PHP, o `.htaccess` não está ativo (Nginx?): **pare e contate o suporte**.
   - [ ] `https://<domínio>/teia/instalar` responde 404.
   - [ ] Login no painel funciona e o cookie `teia_admin` tem `Secure`.
   - [ ] Envie uma imagem pelo painel e veja se ela aparece.
   - [ ] Crie um backup manual e baixe o arquivo.

## Atualizações (deploys seguintes)

1. Faça `git push` no branch configurado. O Git Webhook publica o código.
2. Na primeira visita, migrações pendentes rodam sozinhas, com backup `pre-migracao` antes.
3. Os dados em `teia-ade-sampa-data/` não são tocados pelo deploy.

## Se o servidor for Nginx (sem `.htaccess`)

O código continua protegido: arquivos de `app/` encerram se forem chamados direto, e os dados ficam fora do `www`. Mesmo assim, **as URLs amigáveis não funcionam sem reescrita**. Peça ao suporte da KingHost para:

- apontar o document root para `www/teia/public`; ou
- configurar a reescrita para `public/index.php`.

## Base path

O sistema detecta `/teia` automaticamente. Se um proxy mudar os caminhos, fixe em `config.local.php`: `'base_path' => '/teia'`.
