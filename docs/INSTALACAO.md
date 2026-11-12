# Instalação

## Onde ficam os dados

Os dados persistentes (banco, uploads, backups, logs, sessões e a chave `app.key`) **nunca** ficam no repositório nem na área pública. A pasta é escolhida nesta ordem:

1. Variável de ambiente `TEIA_DATA_DIR`.
2. O arquivo `app/config/data-path.local.php`, que fica fora do Git e deve conter: `<?php return '/caminho/absoluto/teia-ade-sampa-data';`
3. Padrão: a pasta irmã do projeto, `../teia-ade-sampa-data`. Se essa pasta ficar dentro do document root (por exemplo, com o projeto em `/home/site/www/teia`), o sistema usa automaticamente `/home/site/teia-ade-sampa-data`, ao lado de `www`.

O instalador **recusa** continuar se o diretório de dados estiver dentro da área pública.

## Passo a passo

1. Acesse `https://<site>/instalar` (local: `http://localhost:8080/instalar`).
2. **Requisitos.** O instalador verifica:
   - versão do PHP;
   - extensões `pdo_sqlite`, `mbstring`, `fileinfo`, `openssl`, `gd` e `zip`; `sodium`, WebP e Argon2id são recomendados;
   - versão do SQLite;
   - **escrita real** no diretório de dados;
   - **localização** desse diretório (fora da área pública).

   Itens marcados como **FALHOU** impedem a instalação; os marcados como **AVISO** não impedem.
3. Clique em **Preparar instalação**. O sistema cria:
   - as pastas `database/`, `uploads/`, `backups/`, `logs/`, `sessions/` e `tmp/`;
   - a chave `app.key` (aleatória, permissão 0600);
   - o banco, com as migrações;
   - o arquivo `install-token.txt`;
   - um `.htaccess` de bloqueio no diretório de dados.
4. Pelo **FTP**, abra `teia-ade-sampa-data/install-token.txt` e copie a linha de 32 caracteres.
5. Preencha **token**, **nome**, **usuário** e **senha** (mínimo de 12 caracteres; não existe senha padrão) e clique em **Concluir instalação**.
6. Pronto:
   - o token é apagado;
   - `/instalar` passa a responder **404 para sempre**;
   - entre em `/admin`.

São permitidas 5 tentativas de token por IP (e 20 no total) a cada 15 minutos.

## Recuperar o acesso de administrador

Use quando ninguém consegue entrar como administrador, por exemplo se a senha foi esquecida ou a conta foi desativada.

1. Acesse `/admin/recuperar` e clique em **Gerar token**. O site cria `recovery-token.txt` no diretório de dados, válido por **30 minutos**.
2. Pelo FTP, copie o código desse arquivo.
3. Informe o token, o **usuário de um administrador** e a nova senha.
4. A senha é redefinida, a conta é reativada e o token é apagado (é de uso único).

A tela tem as mesmas limitações de tentativas do instalador, e tudo fica registrado na auditoria.

## Reinstalar do zero (desenvolvimento)

Apague o diretório de dados (`../teia-ade-sampa-data`) e acesse `/instalar` de novo. **Nunca faça isso em produção** sem antes baixar um backup.
