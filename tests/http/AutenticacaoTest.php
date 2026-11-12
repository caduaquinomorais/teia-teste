<?php

declare(strict_types=1);

use Teia\Tests\Fixtures;
use Teia\Tests\Http;
use Teia\Tests\T;

$server = null;
$srv = static function () use (&$server) {
    return $server ??= Fixtures::installedServer();
};

$loginAttempt = static function (Http $c, string $user, string $pass): \Teia\Tests\Res {
    $form = $c->get('/admin/login');
    return $c->post('/admin/login', ['_csrf' => Http::csrf($form->body), 'username' => $user, 'password' => $pass]);
};

return [
    'login errado: mensagem genérica (usuário inexistente = senha errada)' => function () use ($srv, $loginAttempt): void {
        $a = $loginAttempt($srv()->client(), 'admin.teia', 'senha errada qualquer');
        $b = $loginAttempt($srv()->client(), 'ninguem.aqui', 'senha errada qualquer');
        T::same(401, $a->status);
        T::same(401, $b->status);
        T::contains('Usuário ou senha inválidos.', $a->body);
        T::contains('Usuário ou senha inválidos.', $b->body);
    },
    'cookie de sessão: HttpOnly, SameSite=Lax, caminho /admin; ID trocado após login' => function () use ($srv): void {
        $c = $srv()->client();
        $c->get('/admin/login');
        $before = $c->cookies['teia_admin'] ?? null;
        T::true($before !== null);
        $r = $c->get('/admin/login');
        $form = Http::csrf($r->body);
        $r = $c->post('/admin/login', ['_csrf' => $form, 'username' => Fixtures::ADMIN_USER, 'password' => Fixtures::ADMIN_PASS]);
        $cookie = implode("\n", $r->setCookies());
        T::contains('HttpOnly', $cookie);
        T::matches('/SameSite=Lax/i', $cookie);
        T::matches('#path=/admin#i', $cookie);
        T::true(($c->cookies['teia_admin'] ?? null) !== $before, 'regeneração do ID de sessão');
    },
    'ID de sessão forjado não é aceito (strict mode)' => function () use ($srv): void {
        $c = $srv()->client();
        $c->cookies['teia_admin'] = 'abcdefghijklmnopqrstuvwxyz123456';
        $c->get('/admin/login');
        T::true(($c->cookies['teia_admin'] ?? '') !== 'abcdefghijklmnopqrstuvwxyz123456');
    },
    'força bruta: usuário bloqueado após 5 falhas (mesmo com a senha certa)' => function () use ($srv, $loginAttempt): void {
        $s = $srv();
        // Usuário próprio para não afetar os outros testes.
        $admin = Fixtures::login($s);
        Fixtures::editor($s, $admin, 'alvo.bruta', ['layout'], 'senha correta do alvo 1');
        for ($i = 0; $i < 5; $i++) {
            T::same(401, $loginAttempt($s->client(), 'alvo.bruta', "errada $i errada")->status);
        }
        $r = $loginAttempt($s->client(), 'alvo.bruta', 'senha correta do alvo 1');
        T::same(429, $r->status);
        T::contains('Muitas tentativas', $r->body);
    },
    'POST de login sem CSRF é recusado' => function () use ($srv): void {
        $r = $srv()->client()->post('/admin/login', ['username' => Fixtures::ADMIN_USER, 'password' => Fixtures::ADMIN_PASS]);
        T::same(403, $r->status);
    },
    'sair encerra a sessão no servidor (cookie antigo não vale mais)' => function () use ($srv): void {
        $c = Fixtures::login($srv());
        $old = $c->cookies;
        [, $csrf] = Fixtures::page($c, '/admin');
        T::same(303, $c->post('/admin/sair', ['_csrf' => $csrf])->status);
        $replay = $srv()->client();
        $replay->cookies = $old;
        $r = $replay->get('/admin');
        T::same(303, $r->status);
        T::contains('/admin/login', $r->location());
    },
    'sessão expira por inatividade' => function () use ($srv): void {
        $s = $srv();
        file_put_contents($s->dataDir . '/config.local.php', "<?php return ['session_idle_timeout' => 2];\n");
        try {
            $c = Fixtures::login($s);
            T::same(200, $c->get('/admin')->status);
            sleep(3);
            $r = $c->get('/admin');
            T::same(303, $r->status);
            T::contains('/admin/login', $r->location());
        } finally {
            unlink($s->dataDir . '/config.local.php');
        }
    },
    'sessão tem expiração absoluta' => function () use ($srv): void {
        $s = $srv();
        file_put_contents($s->dataDir . '/config.local.php', "<?php return ['session_absolute_timeout' => 3];\n");
        try {
            $c = Fixtures::login($s);
            sleep(2);
            T::same(200, $c->get('/admin')->status, 'ainda ativa (atividade recente)');
            sleep(2);
            T::same(303, $c->get('/admin')->status, 'expirou mesmo com atividade');
        } finally {
            unlink($s->dataDir . '/config.local.php');
        }
    },
    'troca de senha exige a senha atual e encerra outras sessões' => function () use ($srv): void {
        $s = $srv();
        $admin = Fixtures::login($s);
        [$ed] = Fixtures::editor($s, $admin, 'troca.senha', ['layout'], 'primeira senha forte 1');
        $other = Fixtures::login($s, 'troca.senha', 'primeira senha forte 1');
        [, $csrf] = Fixtures::page($ed, '/admin/senha');
        $r = $ed->post('/admin/senha', ['_csrf' => $csrf, 'current_password' => 'errada errada errada', 'password' => 'segunda senha forte 2', 'password_confirm' => 'segunda senha forte 2']);
        T::same(422, $r->status);
        [, $csrf] = Fixtures::page($ed, '/admin/senha');
        $r = $ed->post('/admin/senha', ['_csrf' => $csrf, 'current_password' => 'primeira senha forte 1', 'password' => 'segunda senha forte 2', 'password_confirm' => 'segunda senha forte 2']);
        T::same(303, $r->status);
        T::same(200, $ed->get('/admin')->status, 'sessão atual continua');
        T::same(303, $other->get('/admin')->status, 'outra sessão encerrada');
    },
];
