<?php

declare(strict_types=1);

use Teia\Tests\Fixtures;
use Teia\Tests\Http;
use Teia\Tests\T;

$state = null;
$setup = static function () use (&$state): array {
    if ($state === null) {
        $s = Fixtures::installedServer();
        $admin = Fixtures::login($s);
        [$editor, $editorId] = Fixtures::editor($s, $admin, 'editor.layout', ['layout']);
        $state = compact('s', 'admin', 'editor', 'editorId');
    }
    return $state;
};

return [
    'editor só vê as páginas liberadas' => function () use ($setup): void {
        ['editor' => $ed] = $setup();
        $r = $ed->get('/admin');
        T::same(200, $r->status);
        T::contains('/admin/paginas/layout', $r->body);
        T::notContains('/admin/paginas/teia', $r->body);
        T::notContains('/admin/usuarios', $r->body, 'menu de admin oculto');
    },
    'editor: GET e POST em página não autorizada → 403' => function () use ($setup): void {
        ['editor' => $ed] = $setup();
        [, $csrf] = Fixtures::page($ed, '/admin/paginas/layout');
        T::same(403, $ed->get('/admin/paginas/teia')->status);
        T::same(403, $ed->post('/admin/paginas/teia', ['_csrf' => $csrf, 'version' => '0', 'c' => ['banner' => ['titulo' => 'invadido']]])->status);
        T::same(403, $ed->get('/admin/paginas/teia/historico')->status);
        T::same(403, $ed->post('/admin/paginas/teia/restaurar/1', ['_csrf' => $csrf, 'current_version' => '0'])->status);
        T::notContains('invadido', $ed->get('/')->body);
    },
    'editor: áreas de administrador → 403 (inclusive POST direto)' => function () use ($setup): void {
        ['editor' => $ed] = $setup();
        [, $csrf] = Fixtures::page($ed, '/admin/paginas/layout');
        foreach (['/admin/usuarios', '/admin/usuarios/novo', '/admin/usuarios/1', '/admin/backups', '/admin/auditoria'] as $path) {
            T::same(403, $ed->get($path)->status, "GET $path");
        }
        $posts = [
            '/admin/usuarios/novo' => ['username' => 'hacker', 'name' => 'H', 'role' => 'admin'],
            '/admin/usuarios/1' => ['name' => 'X', 'role' => 'editor'],
            '/admin/usuarios/1/status' => ['ativo' => '0'],
            '/admin/usuarios/1/senha' => [],
            '/admin/backups/criar' => [],
            '/admin/backups/restaurar' => ['confirmacao' => 'RESTAURAR', 'name' => 'x'],
            '/admin/backups/configurar' => ['retention' => '1'],
        ];
        foreach ($posts as $path => $data) {
            T::same(403, $ed->post($path, $data + ['_csrf' => $csrf])->status, "POST $path");
        }
    },
    'editor não consegue se promover a administrador' => function () use ($setup): void {
        ['editor' => $ed, 'editorId' => $id] = $setup();
        [, $csrf] = Fixtures::page($ed, '/admin/paginas/layout');
        T::same(403, $ed->post('/admin/usuarios/' . $id, ['_csrf' => $csrf, 'name' => 'Eu', 'role' => 'admin', 'permissions' => ['teia']])->status);
        T::same(403, $ed->get('/admin/paginas/teia')->status);
    },
    'sem login: POST direto em qualquer rota do painel → 403' => function () use ($setup): void {
        ['s' => $s] = $setup();
        $anon = $s->client();
        foreach (['/admin/paginas/teia', '/admin/usuarios/novo', '/admin/backups/criar', '/admin/backups/restaurar', '/admin/senha', '/admin/usuarios/1/status'] as $path) {
            T::same(403, $anon->post($path, ['version' => '0'])->status, $path);
        }
    },
    'CSRF: token de outra sessão e Origin estrangeiro são recusados' => function () use ($setup): void {
        ['s' => $s, 'admin' => $admin] = $setup();
        $other = Fixtures::login($s);
        [, $foreign] = Fixtures::page($other, '/admin');
        T::same(403, $admin->post('/admin/backups/criar', ['_csrf' => $foreign])->status, 'token de outra sessão');
        [, $mine] = Fixtures::page($admin, '/admin');
        T::same(403, $admin->post('/admin/backups/criar', ['_csrf' => $mine], ['Origin' => 'https://evil.example'])->status, 'Origin');
        T::same(403, $admin->post('/admin/backups/criar', ['_csrf' => $mine], ['Referer' => 'https://evil.example/x'])->status, 'Referer');
        T::same(403, $admin->post('/admin/backups/criar', [])->status, 'sem token');
    },
    'revogar permissão encerra a sessão do editor' => function () use ($setup): void {
        ['s' => $s, 'admin' => $admin] = $setup();
        [$ed, $id] = Fixtures::editor($s, $admin, 'editor.revogado', ['layout', 'teia']);
        T::same(200, $ed->get('/admin/paginas/teia')->status);
        [, $csrf] = Fixtures::page($admin, '/admin/usuarios/' . $id);
        T::same(303, $admin->post('/admin/usuarios/' . $id, ['_csrf' => $csrf, 'name' => 'Editor', 'email' => '', 'role' => 'editor', 'permissions' => ['layout']])->status);
        $r = $ed->get('/admin/paginas/teia');
        T::same(303, $r->status, 'sessão encerrada após revogação');
        T::contains('/admin/login', $r->location());
    },
    'desativar conta bloqueia login e encerra sessão; reativar devolve acesso' => function () use ($setup): void {
        ['s' => $s, 'admin' => $admin] = $setup();
        [$ed, $id] = Fixtures::editor($s, $admin, 'editor.inativo', ['layout'], 'senha do inativo 123');
        [, $csrf] = Fixtures::page($admin, '/admin/usuarios/' . $id);
        T::same(303, $admin->post('/admin/usuarios/' . $id . '/status', ['_csrf' => $csrf, 'ativo' => '0'])->status);
        T::same(303, $ed->get('/admin')->status);
        $c = $s->client();
        $form = $c->get('/admin/login');
        $r = $c->post('/admin/login', ['_csrf' => Http::csrf($form->body), 'username' => 'editor.inativo', 'password' => 'senha do inativo 123']);
        T::same(401, $r->status);
        T::contains('Usuário ou senha inválidos.', $r->body);
        [, $csrf] = Fixtures::page($admin, '/admin/usuarios/' . $id);
        T::same(303, $admin->post('/admin/usuarios/' . $id . '/status', ['_csrf' => $csrf, 'ativo' => '1'])->status);
        Fixtures::login($s, 'editor.inativo', 'senha do inativo 123');
    },
    'último administrador ativo: não pode ser desativado nem rebaixado' => function () use ($setup): void {
        ['admin' => $admin] = $setup();
        [, $csrf] = Fixtures::page($admin, '/admin/usuarios/1');
        T::same(403, $admin->post('/admin/usuarios/1/status', ['_csrf' => $csrf, 'ativo' => '0'])->status, 'próprio usuário');
        $r = $admin->post('/admin/usuarios/1', ['_csrf' => $csrf, 'name' => 'Admin', 'email' => '', 'role' => 'editor']);
        T::same(422, $r->status);
        T::contains('última conta de administrador', $r->body);
    },
    'segundo admin não consegue desativar o último admin ativo restante' => function () use ($setup): void {
        ['s' => $s, 'admin' => $admin] = $setup();
        // Promove um editor a admin, desativa o admin original pelo novo, depois tenta desativar o novo pelo... ele mesmo não pode.
        [$ed, $id] = Fixtures::editor($s, $admin, 'segundo.admin', ['layout'], 'senha do segundo 123');
        [, $csrf] = Fixtures::page($admin, '/admin/usuarios/' . $id);
        T::same(303, $admin->post('/admin/usuarios/' . $id, ['_csrf' => $csrf, 'name' => 'Segundo', 'email' => '', 'role' => 'admin'])->status);
        $second = Fixtures::login($s, 'segundo.admin', 'senha do segundo 123');
        [, $csrf2] = Fixtures::page($second, '/admin/usuarios/1');
        T::same(303, $second->post('/admin/usuarios/1/status', ['_csrf' => $csrf2, 'ativo' => '0'])->status, 'havia 2 admins');
        // Agora "segundo.admin" é o único admin ativo: o admin original não existe mais ativo para desativá-lo,
        // e ele não pode desativar a si mesmo.
        T::same(403, $second->post('/admin/usuarios/' . $id . '/status', ['_csrf' => $csrf2, 'ativo' => '0'])->status);
        // Reativa o original para os demais testes.
        T::same(303, $second->post('/admin/usuarios/1/status', ['_csrf' => $csrf2, 'ativo' => '1'])->status);
    },
    'auditoria registra operações administrativas' => function () use ($setup): void {
        ['s' => $s] = $setup();
        $admin = Fixtures::login($s);
        $r = $admin->get('/admin/auditoria');
        T::same(200, $r->status);
        foreach (['Criou usuário', 'Atualizou usuário/permissões', 'Desativou usuário', 'Acesso negado', 'Entrou no painel'] as $label) {
            T::contains($label, $r->body);
        }
        T::notContains('127.0.0.1', $r->body, 'IP puro não aparece');
    },
];
