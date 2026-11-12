<?php
/**
 * @var \Teia\Core\View $view
 * @var array $backups
 * @var int $retention
 * @var ?string $lastAuto
 * @var bool $cronConfigured
 * @var string $cronUrl
 * @var int $maxUpload
 * @var string $csrf
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => 'Backups', 'active' => 'backups']);
$types = ['auto' => 'Automático', 'manual' => 'Manual', 'seguranca' => 'Segurança (antes de restaurar)', 'pre-migracao' => 'Antes de atualização'];
$size = static fn (int $b): string => $b >= 1048576 ? number_format($b / 1048576, 1, ',', '.') . ' MB' : number_format($b / 1024, 0, ',', '.') . ' KB';
?>
<div class="page-head">
    <div>
        <h1>Backups</h1>
        <p class="muted">Cada backup contém o banco de dados (textos, usuários, histórico) e os arquivos enviados (imagens e PDFs).</p>
        <p class="muted">Último backup automático: <?= $lastAuto ? e(date('d/m/Y H:i', (int) $lastAuto)) : 'nenhum ainda' ?>.</p>
    </div>
    <form class="page-head__actions" method="post" action="<?= e(url('/admin/backups/criar')) ?>">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <button class="btn btn--primary" type="submit">Criar backup agora</button>
    </form>
</div>

<?php if ($backups === []): ?>
    <p>Nenhum backup ainda.</p>
<?php else: ?>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th scope="col">Data</th><th scope="col">Tipo</th><th scope="col">Tamanho</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
        <tbody>
        <?php foreach ($backups as $b): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i', $b['created'])) ?></td>
                <td><?= e($types[$b['type']] ?? $b['type']) ?></td>
                <td><?= e($size($b['size'])) ?></td>
                <td class="table__actions">
                    <a class="btn btn--small btn--secondary" href="<?= e(url('/admin/backups/baixar/' . $b['name'])) ?>">Baixar</a>
                    <form method="post" action="<?= e(url('/admin/backups/restaurar')) ?>" data-prompt="Restaurar este backup substitui TODO o conteúdo atual (um backup de segurança será criado antes). Digite RESTAURAR para confirmar.">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="name" value="<?= e($b['name']) ?>">
                        <input type="hidden" name="confirmacao" value="">
                        <button class="btn btn--small btn--secondary" type="submit">Restaurar</button>
                    </form>
                    <form method="post" action="<?= e(url('/admin/backups/excluir')) ?>" data-confirm="Excluir este backup definitivamente?">
                        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
                        <input type="hidden" name="name" value="<?= e($b['name']) ?>">
                        <button class="btn btn--small btn--danger" type="submit">Excluir</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<section class="panel">
    <h2>Restaurar a partir de um arquivo</h2>
    <p class="muted">Use um arquivo .zip baixado deste painel. O conteúdo atual será substituído; antes disso, um backup de segurança é criado automaticamente. Tamanho máximo aceito pelo servidor: <?= e($size($maxUpload)) ?>.</p>
    <form method="post" action="<?= e(url('/admin/backups/restaurar')) ?>" enctype="multipart/form-data" class="stack">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="field">
            <label class="field__label" for="arquivo">Arquivo de backup (.zip)</label>
            <input id="arquivo" name="arquivo" type="file" accept=".zip,application/zip" required>
        </div>
        <div class="field">
            <label class="field__label" for="confirmacao">Para confirmar, digite RESTAURAR</label>
            <input id="confirmacao" name="confirmacao" required autocomplete="off" pattern="RESTAURAR">
        </div>
        <button class="btn btn--danger" type="submit">Restaurar backup</button>
    </form>
</section>

<section class="panel">
    <h2>Configurações</h2>
    <form method="post" action="<?= e(url('/admin/backups/configurar')) ?>" class="stack">
        <input type="hidden" name="_csrf" value="<?= e($csrf) ?>">
        <div class="field">
            <label class="field__label" for="retention">Quantos backups automáticos manter</label>
            <p class="field__help">Os mais antigos são apagados automaticamente. Backups manuais e de segurança não são apagados.</p>
            <input id="retention" name="retention" type="number" min="1" max="365" value="<?= (int) $retention ?>" required>
        </div>
        <div class="field">
            <label class="field__label" for="cron_secret">Código de autenticação do Cronjob (KingHost)</label>
            <p class="field__help">
                O backup automático roda sempre que alguém usa o painel (uma vez por dia). Para garantir um backup diário mesmo sem uso,
                crie um Cronjob diário no painel da KingHost chamando <code><?= e($cronUrl) ?></code> e cole aqui o código de autenticação
                (cabeçalho X-Cron-Auth) mostrado pela KingHost. Situação: <strong><?= $cronConfigured ? 'configurado' : 'não configurado' ?></strong>.
            </p>
            <input id="cron_secret" name="cron_secret" type="password" autocomplete="off" maxlength="200" placeholder="<?= $cronConfigured ? 'Deixe vazio para manter o atual' : '' ?>">
        </div>
        <?php if ($cronConfigured): ?>
            <label class="check"><input type="checkbox" name="cron_remove" value="1"> Remover o código do Cronjob</label>
        <?php endif; ?>
        <button class="btn btn--primary" type="submit">Salvar configurações</button>
    </form>
</section>
