# Manutenção

## Backups

Cada backup é um `.zip` em `<dados>/backups/` com:

- `manifest.json`: aplicação, formato, versão do esquema, data e SHA-256 de cada arquivo;
- `database/teia.sqlite`: cópia consistente do banco (`VACUUM INTO`);
- `uploads/…`: imagens e PDFs enviados.

| Tipo | Quando é criado | Retenção |
|---|---|---|
| Automático | Uma vez por dia: no primeiro uso do painel do dia e/ou pelo Cronjob da KingHost | Os **N** mais recentes (padrão 14; configurável em Backups → Configurações) |
| Manual | Botão "Criar backup agora" | Mantido até alguém excluir |
| Segurança | Antes de qualquer restauração | Mantido |
| Antes de atualização | Antes de migrações de banco | Mantido |

Por que o backup diário acompanha o uso do painel: o conteúdo só muda pelo painel. Assim, enquanto ninguém usa o painel, não há nada novo a salvar. O Cronjob garante a regularidade mesmo assim (DEPLOY.md, passo 7).

### Restaurar pelo painel

Backups → **Restaurar** (na lista ou enviando um `.zip`) → digite `RESTAURAR`. Antes de substituir qualquer coisa, o sistema:

1. valida a integridade (SHA-256), a versão, os nomes das entradas (Zip Slip) e o banco (`integrity_check`);
2. confere se existe administrador ativo no backup;
3. cria um backup de **segurança** do estado atual;
4. troca o banco e a pasta de uploads.

Se a sua sessão for encerrada depois da restauração, entre de novo.

### Backup manual pelo FTP

1. Conecte no FTP e baixe a pasta inteira `teia-ade-sampa-data/`. Ela fica na raiz da conta, ao lado de `www`.
2. O essencial está em `database/teia.sqlite`, `uploads/` e `app.key`. A chave só é necessária para manter válidos os registros de auditoria e o código do cron.
3. Para uma cópia sem risco de pegar o banco no meio de uma gravação, prefira baixar um ZIP pelo painel ou fazer a cópia fora do horário de edição.

### Restauração manual pelo FTP (quando o painel não abre)

1. Baixe uma cópia da pasta `teia-ade-sampa-data/` atual, por segurança.
2. Abra no computador o `.zip` de backup desejado (de `teia-ade-sampa-data/backups/`).
3. Pelo FTP:
   - substitua `teia-ade-sampa-data/database/teia.sqlite` pelo `database/teia.sqlite` do ZIP;
   - substitua o conteúdo de `teia-ade-sampa-data/uploads/` pelo `uploads/` do ZIP.
4. Apague `teia-ade-sampa-data/database/teia.sqlite-journal`, se existir.
5. Acesse o site. Se precisar de acesso de administrador, use `/admin/recuperar`.

## Logs

- Local: `<dados>/logs/app-AAAA-MM-DD.log`, um arquivo por dia.
- Um erro mostrado no site traz um **código de referência** (ex.: `3FA9C21B`). Procure esse código no log para ver os detalhes.
- Apague logs antigos periodicamente pelo FTP; eles não são removidos automaticamente.

## Arquivos enviados que não são mais usados

Imagens substituídas continuam em `uploads/`, porque versões antigas do histórico ainda podem apontá-las. Não há limpeza automática. Se o espaço ficar curto, faça um backup e apague os arquivos sem uso, com orientação técnica.

## Atualizar o código

Faça `git push` no branch publicado (DEPLOY.md). Migrações rodam sozinhas, com backup antes.

## Imagens padrão do Figma

As versões otimizadas ficam em `public/assets/images/`. Para gerá-las de novo a partir dos originais (pasta local `docs/figma/assets-brutos/`, fora do Git):

```bash
bin/php.sh bin/preparar-assets.php
```

## Checklist mensal

- [ ] Backups → existe backup automático recente?
- [ ] Baixe um backup e guarde fora do servidor.
- [ ] Auditoria → algum acesso negado ou login bloqueado incomum?
- [ ] Usuários → alguém que saiu da equipe ainda está ativo?
