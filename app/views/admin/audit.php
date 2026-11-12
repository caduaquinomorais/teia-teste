<?php
/**
 * @var \Teia\Core\View $view
 * @var array $rows
 * @var int $page
 * @var bool $hasNext
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => 'Auditoria', 'active' => 'auditoria']);
$labels = [
    'login.sucesso' => 'Entrou no painel', 'login.falha' => 'Tentativa de login falhou', 'login.bloqueado' => 'Login bloqueado (muitas tentativas)',
    'logout' => 'Saiu do painel', 'senha.alterada' => 'Alterou a própria senha', 'pagina.publicada' => 'Publicou página',
    'pagina.restaurada' => 'Restaurou versão', 'pagina.conflito' => 'Conflito de edição', 'acesso.negado' => 'Acesso negado',
    'usuario.criado' => 'Criou usuário', 'usuario.atualizado' => 'Atualizou usuário/permissões', 'usuario.senha_redefinida' => 'Redefiniu senha',
    'usuario.desativado' => 'Desativou usuário', 'usuario.reativado' => 'Reativou usuário', 'backup.criado' => 'Criou backup',
    'backup.baixado' => 'Baixou backup', 'backup.excluido' => 'Excluiu backup', 'backup.restaurado' => 'Restaurou backup',
    'backup.restauracao_recusada' => 'Restauração recusada', 'backup.configuracao' => 'Alterou configurações de backup',
    'instalacao.concluida' => 'Instalação concluída', 'recuperacao.token_gerado' => 'Gerou token de recuperação',
    'recuperacao.token_invalido' => 'Token de recuperação inválido', 'recuperacao.concluida' => 'Recuperou acesso de administrador',
];
?>
<div class="page-head"><div><h1>Auditoria</h1><p class="muted">Registro das operações administrativas. Endereços IP são guardados apenas de forma irreversível (código).</p></div></div>
<div class="table-wrap">
    <table class="table table--compact">
        <thead><tr><th scope="col">Data</th><th scope="col">Usuário</th><th scope="col">Ação</th><th scope="col">Alvo</th><th scope="col">Detalhes</th><th scope="col">Origem</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $row): ?>
            <tr>
                <td><?= e(date('d/m/Y H:i:s', strtotime((string) $row['created_at']))) ?></td>
                <td><?= e($row['username'] ?? '—') ?></td>
                <td><?= e($labels[$row['action']] ?? $row['action']) ?></td>
                <td><?= e($row['target'] ?? '') ?></td>
                <td><code class="small"><?= e($row['details'] ?? '') ?></code></td>
                <td><code class="small"><?= e($row['ip_hash'] ?? '') ?></code></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<nav class="pager" aria-label="Paginação">
    <?php if ($page > 1): ?><a class="btn btn--small btn--secondary" href="<?= e(url('/admin/auditoria?p=' . ($page - 1))) ?>">← Mais recentes</a><?php endif; ?>
    <?php if ($hasNext): ?><a class="btn btn--small btn--secondary" href="<?= e(url('/admin/auditoria?p=' . ($page + 1))) ?>">Mais antigos →</a><?php endif; ?>
</nav>
