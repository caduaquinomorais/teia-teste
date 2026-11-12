<?php
/**
 * @var \Teia\Core\View $view
 * @var array $user
 * @var array $list
 * @var array $pages
 * @var string $csrf
 */
defined('TEIA_ROOT') || exit;
$view->layout('admin/layout', ['title' => 'Usuários', 'active' => 'usuarios']);
?>
<div class="page-head">
    <div>
        <h1>Usuários</h1>
        <p class="muted">Administradores editam tudo e gerenciam usuários e backups. Editores só editam as páginas liberadas para eles.</p>
    </div>
    <div class="page-head__actions"><a class="btn btn--primary" href="<?= e(url('/admin/usuarios/novo')) ?>">+ Novo usuário</a></div>
</div>
<div class="table-wrap">
    <table class="table">
        <thead><tr><th scope="col">Nome</th><th scope="col">Usuário</th><th scope="col">Papel</th><th scope="col">Páginas</th><th scope="col">Situação</th><th scope="col">Último acesso</th><th scope="col"><span class="sr-only">Ações</span></th></tr></thead>
        <tbody>
        <?php foreach ($list as $u): ?>
            <tr<?= (int) $u['active'] === 1 ? '' : ' class="is-muted"' ?>>
                <td><?= e($u['name']) ?><?= (int) $u['id'] === (int) $user['id'] ? ' <span class="badge">você</span>' : '' ?></td>
                <td><code><?= e($u['username']) ?></code></td>
                <td><?= $u['role'] === 'admin' ? 'Administrador' : 'Editor' ?></td>
                <td>
                    <?php if ($u['role'] === 'admin'): ?>Todas<?php elseif ($u['permissions'] === []): ?><span class="muted">Nenhuma</span><?php else: ?>
                        <?= e(implode(', ', array_map(static fn ($s) => $pages[$s]['title'] ?? $s, $u['permissions']))) ?>
                    <?php endif; ?>
                </td>
                <td><?= (int) $u['active'] === 1 ? ((int) $u['must_change_password'] === 1 ? 'Ativo (senha temporária)' : 'Ativo') : 'Desativado' ?></td>
                <td><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime((string) $u['last_login_at']))) : '—' ?></td>
                <td><a class="btn btn--small btn--secondary" href="<?= e(url('/admin/usuarios/' . (int) $u['id'])) ?>">Gerenciar</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
