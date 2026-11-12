# Deploy na KingHost

> **Situação:** o deploy real **ainda não foi feito nem validado**. O procedimento se baseia na documentação oficial da KingHost (wiki king.host, consultada em 12/11/2026). Os itens marcados como ⚠️ precisam ser confirmados no servidor.

Sem o CMS, o site não precisa de banco, instalação, diretório de dados, extensões PHP extras nem tarefas agendadas.

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

```
/home/<site>/www/
├── (outros sites/projetos da ADE SAMPA)
└── teia/                  ← "Diretório para Publicação" do Git Webhook (vazio na 1ª vez)
    ├── .htaccess          ← encaminha para public/ e bloqueia o código
    ├── public/
    └── app/ …
```

O site fica em `https://<domínio>/teia/`. Se o painel permitir apontar um domínio ou subdomínio direto para `www/teia/public`, melhor ainda.

## Primeiro deploy

1. **Repositório:** suba o projeto para o GitHub, num repositório **privado**.
2. **PHP:** no painel KingHost → *Configuração PHP*, escolha **8.1 ou superior** (de preferência 8.3).
3. **Git Webhook:** no painel → *Git Webhook*:
   - conecte o GitHub;
   - escolha o repositório e o branch `main`;
   - defina o **Diretório para Publicação** como `www/teia` (vazio).
4. **HTTPS:** ative o Let's Encrypt em *Certificado SSL*. Depois, em `public/.htaccess`, descomente o bloco "Força HTTPS" e faça push.
5. **Conferência:**
   - [ ] `https://<domínio>/teia/` abre o site, com fontes e imagens.
   - [ ] `https://<domínio>/teia/app/content/teia.php` responde **404**. Se mostrar código ou um erro do PHP, o `.htaccess` não está ativo (Nginx?): **pare e contate o suporte**.
   - [ ] `https://<domínio>/teia/nao-existe` mostra a página 404 do Teia.
   - [ ] Os botões "Faça seu agendamento" levam a `https://app.adesampa.com.br/welcome`.
   - [ ] O mapa carrega.

## Atualizações

Edite os arquivos (ver CONTEUDO.md), faça commit e `git push` no branch configurado. O Git Webhook publica o código.

## Se o servidor for Nginx (sem `.htaccess`)

Os arquivos de `app/` encerram sem executar nada se forem chamados diretamente, mas as URLs não funcionam sem reescrita. Peça ao suporte da KingHost para:

- apontar o document root para `www/teia/public`; ou
- configurar a reescrita para `public/index.php`.

## Base path

O sistema detecta `/teia` automaticamente. Se um proxy mudar os caminhos, crie `app/config/app.local.php` no servidor com:

```php
<?php return ['base_path' => '/teia'];
```

Esse arquivo fica fora do Git. Se o Git Webhook o apagar num deploy, recrie.
