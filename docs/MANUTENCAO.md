# Manutenção

## Atualizar conteúdo ou código

1. Edite os arquivos (conteúdo: [CONTEUDO.md](CONTEUDO.md)).
2. Rode `bin/php.sh tests/run.php` e, para mudanças visuais, `tests/visual/executar.sh`.
3. Faça commit num branch, abra o Pull Request e faça o merge com os testes verdes. O GitHub Actions publica na KingHost (ver [DEPLOY.md](DEPLOY.md)).

## Backup

O site não tem banco nem uploads: **o repositório Git é o backup**. Mantenha o GitHub como cópia principal. Para voltar uma alteração, use `git revert <commit>` e faça push.

## Logs

Erros inesperados mostram ao visitante um **código de referência** (ex.: `3FA9C21B`). Os detalhes vão para o log de erros do PHP da hospedagem, com o prefixo `[TEIA]`. Procure o código lá. Na KingHost, consulte os logs pelo painel ou peça ao suporte.

## Imagens

As versões otimizadas ficam em `public/assets/images/` (WebP + JPG). Para gerá-las de novo a partir dos originais do Figma (pasta local `docs/figma/assets-brutos/`, fora do Git):

```bash
bin/php.sh bin/preparar-assets.php
```

## Recuperar a versão com painel (CMS)

A versão com CMS, banco SQLite, usuários e backups está preservada na tag `com-cms`:

```bash
git checkout com-cms
```

## Checklist mensal

- [ ] O site abre e o mapa carrega.
- [ ] Os links institucionais (`TODO-LINK`) já podem ser preenchidos?
- [ ] O certificado HTTPS está válido? A renovação do Let's Encrypt é automática.
